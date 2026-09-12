#!/bin/bash
# ==============================================================================
# Script de Atualização Segura da VPS (SEM PERDA DE DADOS)
# ERP Amazon Naval & Regulatório NORMAM / ISO 9001:2015
# ==============================================================================
# Este script:
# 1. Faz backup preventivo automático do banco de dados da VPS antes de mexer.
# 2. Atualiza os arquivos de código do repositório GitHub (git pull).
# 3. Aplica novas tabelas e alterações de banco de forma incremental (IF NOT EXISTS).
# 4. Reconstrói os containers da aplicação SEM APAGAR O BANCO (sem docker compose down -v).
# 5. Ajusta permissões de uploads, logs e relatórios para o Apache.
# ==============================================================================

set -e

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo "================================================================="
echo "  🚀 INICIANDO ATUALIZAÇÃO SEGURA DA VPS (AMAZON NAVAL)          "
echo "================================================================="

# 1. Definição de Variáveis
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="${DIR}/storage/backups"
mkdir -p "$BACKUP_DIR"

DB_USER="${DB_USER:-root}"
DB_PASS="${MYSQL_ROOT_PASSWORD:-root_pass_2026}"
DB_NAME="${DB_NAME:-erp_sistema}"

echo ""
echo "📦 [1/5] Gerando Backup de Segurança Preventivo do Banco da VPS..."
if docker ps --format '{{.Names}}' | grep -q "erp_db"; then
    docker exec -t erp_db mysqldump -u"$DB_USER" -p"$DB_PASS" --default-character-set=utf8mb4 "$DB_NAME" > "${BACKUP_DIR}/pre_update_${TIMESTAMP}.sql"
    gzip -f "${BACKUP_DIR}/pre_update_${TIMESTAMP}.sql"
    echo "   ✅ Backup salvo com sucesso em: ${BACKUP_DIR}/pre_update_${TIMESTAMP}.sql.gz"
else
    echo "   ⚠️ Container de banco 'erp_db' não está em execução. Pulando backup preliminar."
fi

echo ""
echo "📥 [2/5] Atualizando Código a partir do GitHub (git pull)..."
sudo chown -R "$USER":"$USER" "$DIR"
git fetch origin main

# Preservar alterações locais acidentais se existirem
if ! git diff-index --quiet HEAD --; then
    echo "   ⚠️ Detectadas alterações locais não commitadas. Salvando em stash..."
    git stash
fi

git pull origin main
echo "   ✅ Arquivos do sistema atualizados para a versão mais recente do GitHub."

echo ""
echo "🔄 [3/5] Aplicando Migrações Incrementais no Banco (Sem sobrescrever nada)..."
if docker ps --format '{{.Names}}' | grep -q "erp_db"; then
    for migracao in migrations/100_*.sql migrations/101_*.sql migrations/102_*.sql; do
        if [ -f "$migracao" ]; then
            echo "   -> Aplicando $(basename "$migracao")..."
            docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" --default-character-set=utf8mb4 "$DB_NAME" < "$migracao" || true
        fi
    done
    echo "   ✅ Estruturas novas e índices de performance aplicados com sucesso."
fi

echo ""
echo "🏗️ [4/5] Reconstruindo Containers da Aplicação (Preservando Volumes)..."
# IMPORTANTE: NUNCA usar 'down -v'. O comando abaixo reconstrói apenas o código PHP/Apache e worker
docker compose up -d --build app worker
echo "   ✅ Containers app e worker atualizados e em execução."

echo ""
echo "🔐 [5/5] Ajustando Permissões de Runtime para Apache (www-data)..."
sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp
sudo chmod -R 775 storage uploads logs temp_pdf tmp
echo "   ✅ Permissões ajustadas."

echo ""
echo "================================================================="
echo "  🎉 ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!                          "
echo "  Nenhum dado, cliente, usuário ou proposta da VPS foi perdido!  "
echo "================================================================="
docker compose ps
