# Publicação e recuperação segura

O GitHub guarda o código e um único pacote de recuperação criptografado em
`recovery/amazon-recovery-latest.enc`. Ele contém a configuração `.env`, o
banco MySQL, documentos em `storage/` (exceto sessões e backups operacionais),
uploads e os objetos do MinIO. A senha **não** fica no GitHub.

> **VPS existente:** antes de atualizar para esta versão, não execute `git
> reset --hard`, `git clean` ou `git pull` diretamente. Esses comandos podem
> remover os documentos antigos que deixaram de ser versionados. Primeiro gere
> e valide uma cópia recuperável da VPS atual; só então atualize e restaure.

## Publicar do Windows

Guarde uma senha longa em um gerenciador de senhas e, no PowerShell aberto
para esta publicação, informe-a sem gravá-la em arquivo:

```powershell
$env:RECOVERY_BACKUP_PASSWORD = Read-Host 'Senha do backup' -AsSecureString |
  ConvertFrom-SecureString -AsPlainText
powershell -ExecutionPolicy Bypass -File .\scripts\publicar_tudo_github.ps1 `
  -Mensagem 'Atualizacao com backup recuperavel'
```

O processo exporta o banco, pausa somente o MinIO enquanto copia seus objetos,
criptografa o pacote, testa a descriptografia e valida o ZIP. Se qualquer etapa
falhar, a publicação é interrompida antes do commit.

## Restaurar em VPS nova

Instale Docker, Git, OpenSSL e Unzip, clone o repositório e defina a mesma
senha que foi usada na publicação:

```bash
sudo apt update && sudo apt install -y docker.io docker-compose-plugin openssl unzip git
git clone https://github.com/ronokedas/amazon-branco.git /opt/sistema-amazon
cd /opt/sistema-amazon
export RECOVERY_BACKUP_PASSWORD='senha guardada no gerenciador'
chmod +x scripts/restaurar_recuperacao.sh scripts/corrigir_permissoes_runtime.sh
./scripts/restaurar_recuperacao.sh
docker compose up -d --build
```

O script confirma o hash do pacote, valida a senha e o conteúdo antes de
extrair. Ele recusa sobrescrever `storage`, `uploads` ou `minio-data`; para
uma restauração conscientemente substitutiva use `./scripts/restaurar_recuperacao.sh recovery/amazon-recovery-latest.enc --forcar`.

Em uma VPS com arquivos recém-restaurados e permissões inválidas, execute uma
vez (não no boot):

```bash
docker compose exec -T -u root app sh scripts/corrigir_permissoes_runtime.sh
docker compose up -d --force-recreate app worker
```

## O que não é backup

`storage/sessions`, `logs`, `tmp`, `temp_pdf` e `storage/backups` são dados
temporários/operacionais e não devem ser copiados para o Git nem para o pacote
mestre. Sessões expiram após 14 dias. PDFs, anexos, banco, MinIO e `.env` são
os dados necessários para recuperar o sistema.
