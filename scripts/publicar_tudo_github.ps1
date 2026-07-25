param(
    [string]$Mensagem = ("Sincroniza sistema completo em " + (Get-Date -Format "yyyy-MM-dd HH:mm:ss")),
    [string]$Remote = "origin",
    [string]$Branch = "main",
    [switch]$SemExportarBanco
)

$ErrorActionPreference = "Stop"
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $raiz

if (-not $SemExportarBanco) {
    $container = docker compose ps -q db
    if (-not $container) {
        throw "O container do MySQL nao esta em execucao. Suba o Docker ou use -SemExportarBanco."
    }

    Write-Host "Exportando o banco completo para db.sql..."
    docker exec $container sh -c 'exec mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 --no-tablespaces "$MYSQL_DATABASE" > /tmp/erp_db_completo.sql'
    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao exportar o banco de dados."
    }

    docker cp "${container}:/tmp/erp_db_completo.sql" "$raiz\db.sql"
    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao copiar o dump para db.sql."
    }

    docker exec $container rm -f /tmp/erp_db_completo.sql
}

if (-not (Test-Path -LiteralPath "$raiz\minio-data")) {
    New-Item -ItemType Directory -Path "$raiz\minio-data" | Out-Null
}

$minioContainer = docker compose ps -q minio
if ($minioContainer) {
    Write-Host "Sincronizando os objetos do MinIO para minio-data..."
    docker cp "${minioContainer}:/data/." "$raiz\minio-data"
    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao copiar os objetos do MinIO."
    }
} else {
    Write-Warning "MinIO parado: publicando o conteudo atual de minio-data."
}

$servicosAtivos = @(docker compose ps --services --filter status=running)
$containersParados = $false

try {
    if ($servicosAtivos.Count -gt 0) {
        Write-Host "Pausando os containers para criar um snapshot consistente..."
        docker compose stop
        if ($LASTEXITCODE -ne 0) {
            throw "Falha ao pausar os containers."
        }
        $containersParados = $true
    }

    Write-Host "Adicionando todos os arquivos, sem excecao..."
    git add --all
    if ($LASTEXITCODE -ne 0) {
        throw "Falha no git add --all."
    }

    $alteracoes = git diff --cached --name-only
    if (-not $alteracoes) {
        Write-Host "Nao ha alteracoes para publicar."
        exit 0
    }

    git commit -m $Mensagem
    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao criar o commit."
    }

    git push $Remote "HEAD:$Branch"
    if ($LASTEXITCODE -ne 0) {
        throw "O commit foi criado, mas o envio ao GitHub falhou."
    }

    Write-Host "Sistema completo publicado em $Remote/$Branch."
} finally {
    if ($containersParados) {
        Write-Host "Religando os containers locais..."
        docker compose start
    }
}
