param(
    [string]$Destino = "recovery"
)

$ErrorActionPreference = 'Stop'
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $raiz

if ([string]::IsNullOrWhiteSpace($env:RECOVERY_BACKUP_PASSWORD)) {
    throw 'Defina RECOVERY_BACKUP_PASSWORD antes de criar o backup de recuperacao.'
}

$destinoAbsoluto = Join-Path $raiz $Destino
$work = Join-Path $destinoAbsoluto '.work'
$stage = Join-Path $work 'conteudo'
$zip = Join-Path $work 'recuperacao.zip'
$encryptedNext = Join-Path $work 'amazon-recovery-latest.enc.next'
$manifestNext = Join-Path $work 'amazon-recovery-latest.manifest.json.next'
$encryptedFinal = Join-Path $destinoAbsoluto 'amazon-recovery-latest.enc'
$manifestFinal = Join-Path $destinoAbsoluto 'amazon-recovery-latest.manifest.json'
$minioStopped = $false

function Copy-DirectoryContents([string]$Source, [string]$Target) {
    New-Item -ItemType Directory -Force -Path $Target | Out-Null
    if (Test-Path -LiteralPath $Source) {
        Get-ChildItem -LiteralPath $Source -Force | ForEach-Object {
            Copy-Item -LiteralPath $_.FullName -Destination $Target -Recurse -Force
        }
    }
}

function Get-ManifestFiles([string]$Root) {
    @(Get-ChildItem -LiteralPath $Root -Recurse -File -Force | ForEach-Object {
        [ordered]@{
            path = $_.FullName.Substring($Root.Length + 1).Replace('\', '/')
            bytes = $_.Length
            sha256 = (Get-FileHash -Algorithm SHA256 -LiteralPath $_.FullName).Hash.ToLowerInvariant()
        }
    })
}

try {
    New-Item -ItemType Directory -Force -Path $stage | Out-Null

    $dbContainer = docker compose ps -q db
    if (-not $dbContainer) { throw 'O MySQL nao esta em execucao; o backup nao pode ser criado.' }

    Write-Host 'Exportando banco de dados...'
    $dbDump = Join-Path $stage 'db.sql'
    & docker compose exec -T db sh -c 'exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 --no-tablespaces "$MYSQL_DATABASE"' > $dbDump
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $dbDump) -or (Get-Item $dbDump).Length -eq 0) {
        throw 'Falha ao exportar o banco de dados.'
    }

    $envSnapshot = Join-Path $stage '.env'
    $envLocal = Join-Path $raiz '.env'
    if (Test-Path -LiteralPath $envLocal) {
        Copy-Item -LiteralPath $envLocal -Destination $envSnapshot -Force
    } else {
        # Alguns ambientes antigos dependem apenas dos valores padrao do
        # Compose. Nessa situacao, registra a configuracao efetivamente usada
        # pelo container, sem criar um .env desprotegido no diretorio raiz.
        Copy-Item -LiteralPath (Join-Path $raiz '.env.example') -Destination $envSnapshot -Force
        $runningEnvironment = & docker compose exec -T app sh -c 'env'
        if ($LASTEXITCODE -ne 0) { throw 'Nao foi possivel ler a configuracao do container app.' }
        $values = @{}
        foreach ($line in $runningEnvironment) {
            $pair = $line -split '=', 2
            if ($pair.Count -eq 2) { $values[$pair[0]] = $pair[1] }
        }
        $content = Get-Content -LiteralPath $envSnapshot -Raw
        foreach ($key in $values.Keys) {
            $escaped = [regex]::Escape($key)
            if ($content -match "(?m)^$escaped=") {
                $content = [regex]::Replace($content, "(?m)^$escaped=.*$", "$key=$($values[$key])")
            }
        }
        Set-Content -LiteralPath $envSnapshot -Value $content -Encoding utf8NoBOM
        Write-Warning 'Arquivo .env ausente: o backup contem um .env reconstruido a partir do container em execucao.'
    }
    $storageTarget = Join-Path $stage 'storage'
    New-Item -ItemType Directory -Force -Path $storageTarget | Out-Null
    Get-ChildItem -LiteralPath (Join-Path $raiz 'storage') -Force | Where-Object { $_.Name -notin @('sessions', 'backups') } | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $storageTarget -Recurse -Force
    }
    Copy-DirectoryContents (Join-Path $raiz 'uploads') (Join-Path $stage 'uploads')

    # O MinIO e interrompido apenas durante a copia para evitar um snapshot
    # inconsistente de metadados e objetos.
    if (docker compose ps -q minio) {
        Write-Host 'Pausando MinIO para copiar objetos de forma consistente...'
        docker compose stop minio
        if ($LASTEXITCODE -ne 0) { throw 'Falha ao pausar o MinIO.' }
        $minioStopped = $true
    }
    Copy-DirectoryContents (Join-Path $raiz 'minio-data') (Join-Path $stage 'minio-data')

    $files = Get-ManifestFiles $stage
    $required = @('.env', 'db.sql', 'storage', 'uploads', 'minio-data')
    foreach ($entry in $required) {
        if (-not (Test-Path -LiteralPath (Join-Path $stage $entry))) { throw "Componente obrigatorio ausente: $entry" }
    }

    Write-Host 'Compactando e criptografando pacote de recuperacao...'
    & docker compose exec -T -e "RECOVERY_BACKUP_PASSWORD=$($env:RECOVERY_BACKUP_PASSWORD)" app sh -c 'set -eu; cd /var/www/html/recovery/.work/conteudo; zip -q -r ../recuperacao.zip .; openssl enc -aes-256-cbc -pbkdf2 -iter 600000 -salt -in ../recuperacao.zip -out ../amazon-recovery-latest.enc.next -pass env:RECOVERY_BACKUP_PASSWORD; openssl enc -d -aes-256-cbc -pbkdf2 -iter 600000 -in ../amazon-recovery-latest.enc.next -out ../validacao.zip -pass env:RECOVERY_BACKUP_PASSWORD; unzip -tq ../validacao.zip; rm -f ../validacao.zip'
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $encryptedNext)) { throw 'O pacote criptografado nao passou na validacao.' }

    $manifest = [ordered]@{
        formato = 'amazon-recovery-v1'
        gerado_em_utc = (Get-Date).ToUniversalTime().ToString('o')
        criptografia = [ordered]@{ algoritmo = 'AES-256-CBC'; kdf = 'PBKDF2'; iteracoes = 600000; comando = 'openssl enc' }
        arquivo = [ordered]@{ nome = 'amazon-recovery-latest.enc'; bytes = (Get-Item $encryptedNext).Length; sha256 = (Get-FileHash -Algorithm SHA256 -LiteralPath $encryptedNext).Hash.ToLowerInvariant() }
        componentes = $required
        arquivos = $files
    }
    $manifest | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $manifestNext -Encoding utf8NoBOM

    Move-Item -LiteralPath $encryptedNext -Destination $encryptedFinal -Force
    Move-Item -LiteralPath $manifestNext -Destination $manifestFinal -Force
    Write-Host "Backup validado: $encryptedFinal"
}
finally {
    if ($minioStopped) { docker compose start minio | Out-Host }
    if (Test-Path -LiteralPath $work) { Remove-Item -LiteralPath $work -Recurse -Force }
}
