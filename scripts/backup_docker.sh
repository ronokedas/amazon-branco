#!/bin/bash
# Script de Backup Automatizado para ambiente Docker em VPS
# Salve este script em uma pasta do servidor VPS (ex: na raiz do projeto) e dê permissão de execução:
# chmod +x scripts/backup_docker.sh

# Vá para o diretório raiz do projeto (ajuste se necessário)
# Isso garante que os caminhos relativos funcionem se chamado via cron
cd "$(dirname "$0")/.." || exit 1

BACKUP_DIR="storage/backups"
CONTAINER_DB="erp_db"

read_env_value() {
    [ -f .env ] || return 0
    value=$(grep -m1 "^${1}=" .env | cut -d= -f2-)
    value=${value#\"}; value=${value%\"}
    value=${value#\'}; value=${value%\'}
    printf '%s' "$value"
}

DB_USER="${DB_USER:-$(read_env_value DB_USER)}"; DB_USER="${DB_USER:-erp_user}"
DB_PASS="${DB_PASS:-$(read_env_value DB_PASS)}"; DB_PASS="${DB_PASS:-erp_pass_2026}"
DB_NAME="${DB_NAME:-$(read_env_value DB_NAME)}"; DB_NAME="${DB_NAME:-erp_sistema}"

# Criar pasta de backup se não existir
mkdir -p "$BACKUP_DIR" storage/private

TIMESTAMP=$(date +"%Y-%m-%d_%H%M")
BACKUP_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.sql"
GZ_FILE="${BACKUP_FILE}.gz"
PRIVATE_FILE="${BACKUP_DIR}/private_${TIMESTAMP}.tar.gz"
MINIO_FILE="${BACKUP_DIR}/minio_${TIMESTAMP}.tar.gz"

echo "Iniciando backup do container ${CONTAINER_DB}..."

# Executa mysqldump de dentro do container MySQL e salva no host
docker exec "$CONTAINER_DB" /usr/bin/mysqldump --no-tablespaces --single-transaction --routines --triggers -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo "Erro ao gerar o backup do banco de dados."
    # Remove arquivo vazio se falhar
    rm -f "$BACKUP_FILE"
    exit 1
fi

echo "Backup gerado. Compactando..."
gzip -f "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo "Erro ao compactar o backup."
    exit 1
fi

echo "Backup concluído: ${GZ_FILE}"

echo "Copiando arquivos privados..."
tar -czf "$PRIVATE_FILE" storage/private
if [ $? -ne 0 ]; then echo "Erro ao copiar storage/private."; exit 1; fi

echo "Copiando o volume do MinIO..."
docker run --rm --volumes-from erp_minio alpine:3.20 tar -czf - /data > "$MINIO_FILE"
if [ $? -ne 0 ] || [ ! -s "$MINIO_FILE" ]; then echo "Erro ao copiar o volume do MinIO."; rm -f "$MINIO_FILE"; exit 1; fi

sha256sum "$GZ_FILE" "$PRIVATE_FILE" "$MINIO_FILE" > "${BACKUP_DIR}/checksums_${TIMESTAMP}.sha256"

# Limpar backups antigos - Mantém apenas os últimos 30
echo "Limpando backups mais antigos que 30 dias (mantendo 30 arquivos)..."
# Lista os arquivos gz, ordena por data invertida, pula os 30 primeiros e remove o resto
ls -1t "$BACKUP_DIR"/backup_*.sql.gz 2>/dev/null | tail -n +31 | xargs -r rm -f
ls -1t "$BACKUP_DIR"/private_*.tar.gz 2>/dev/null | tail -n +31 | xargs -r rm -f
ls -1t "$BACKUP_DIR"/minio_*.tar.gz 2>/dev/null | tail -n +31 | xargs -r rm -f
ls -1t "$BACKUP_DIR"/checksums_*.sha256 2>/dev/null | tail -n +31 | xargs -r rm -f

echo "Processo finalizado com sucesso."
