param(
    [string]$Mensagem = ("Sincroniza sistema completo em " + (Get-Date -Format "yyyy-MM-dd HH:mm:ss")),
    [string]$Remote = "origin",
    [string]$Branch = "main",
    [switch]$SemExportarBanco
)

$ErrorActionPreference = "Stop"
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $raiz

if ($SemExportarBanco) {
    Write-Warning '-SemExportarBanco foi descontinuado: toda publicacao exige um backup recuperavel atual.'
}

Write-Host "Criando e validando o pacote criptografado de recuperacao..."
& "$PSScriptRoot\criar_backup_recuperacao.ps1"
if ($LASTEXITCODE -ne 0) { throw "Falha ao criar backup recuperavel; nada foi publicado." }

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

    Write-Host "Adicionando codigo e pacote criptografado, sem dados de runtime..."
    # Nao use a raiz como pathspec: o Git retorna erro ao encontrar os
    # diretorios de dados propositalmente ignorados. A lista explicita tambem
    # evita enviar vendor/node_modules por acidente.
    git add -u -- .
    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao atualizar arquivos ja versionados."
    }
    $fontes = @(
        '.gitignore', '.htaccess', '.dockerignore', '.env.example',
        'ajax', 'assets', 'campo', 'docker', 'docs', 'includes', 'migrations',
        'modules', 'scripts', 'templates', 'tests',
        'composer.json', 'composer.lock', 'config.php', 'docker-compose.yml',
        'Dockerfile', 'index.php', 'php.ini',
        'pwa-campo/src', 'pwa-campo/package.json', 'pwa-campo/package-lock.json',
        'pwa-campo/vite.config.js', 'pwa-campo/vite.config.ts', 'pwa-campo/index.html'
    ) | Where-Object { Test-Path -LiteralPath $_ }
    if ($fontes.Count -gt 0) { git add -- $fontes }
    if ($LASTEXITCODE -ne 0) {
        throw "Falha no git add --all."
    }

    # Arquivos de recuperacao sao ignorados por seguranca para que um pacote
    # de teste nunca seja publicado por engano. Somente o pacote validado
    # nesta execucao e explicitamente liberado para o commit.
    git add -f recovery/amazon-recovery-latest.enc recovery/amazon-recovery-latest.manifest.json
    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao adicionar o pacote criptografado de recuperacao."
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
