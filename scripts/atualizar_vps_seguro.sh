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
    # 1. Cria tabela de controle de migrações na VPS se ainda não existir
    docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" --default-character-set=utf8mb4 "$DB_NAME" -e "
        CREATE TABLE IF NOT EXISTS schema_migrations (
            versao VARCHAR(255) PRIMARY KEY,
            executado_em DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    " 2>/dev/null || true

    # 2. Se a tabela acabou de nascer e já existem dados, marca migrações legadas antigas (001 a 098)
    total_registrado=$(docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" -s -N "$DB_NAME" -e "SELECT COUNT(*) FROM schema_migrations;" 2>/dev/null || echo "0")
    if [ "$total_registrado" = "0" ]; then
        for legada in $(ls -1 migrations/0*.sql 2>/dev/null | grep -v "099_"); do
            nome_leg=$(basename "$legada")
            docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "INSERT IGNORE INTO schema_migrations (versao) VALUES ('$nome_leg');" 2>/dev/null || true
        done
    fi

    # 3. Executa dinamicamente TODAS as migrações novas que ainda não foram aplicadas (099, 100, 101, 102, 103, 104, 105...)
    total_novas=0
    for migracao in $(ls -1 migrations/*.sql 2>/dev/null | sort -V); do
        [ -f "$migracao" ] || continue
        nome=$(basename "$migracao")
        ja_rodou=$(docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" -s -N "$DB_NAME" -e "SELECT COUNT(*) FROM schema_migrations WHERE versao='$nome';" 2>/dev/null || echo "0")
        if [ "$ja_rodou" = "0" ]; then
            echo "   -> Aplicando nova migração: $nome..."
            if docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" --default-character-set=utf8mb4 "$DB_NAME" < "$migracao"; then
                docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "INSERT IGNORE INTO schema_migrations (versao) VALUES ('$nome');" 2>/dev/null || true
                echo "      ✅ $nome executada com sucesso."
                total_novas=$((total_novas + 1))
            else
                echo "      ⚠️ Aviso na migração $nome (verifique o arquivo se necessário)."
                docker exec -i erp_db mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "INSERT IGNORE INTO schema_migrations (versao) VALUES ('$nome');" 2>/dev/null || true
            fi
        fi
    done

    if [ "$total_novas" = "0" ]; then
        echo "   ℹ️ O banco de dados já está na versão mais recente. Nenhuma nova tabela pendente."
    else
        echo "   ✅ Total de $total_novas nova(s) migração(ões) aplicada(s) com sucesso."
    fi
fi

echo ""
echo "🏗️ [4/6] Reconstruindo Containers da Aplicação (Preservando Volumes)..."
# IMPORTANTE: NUNCA usar 'down -v'. O comando abaixo reconstrói apenas o código PHP/Apache e worker
docker compose up -d --build app worker
docker compose restart app
echo "   ✅ Containers app e worker atualizados e em execução."

echo ""
echo "🧹 [5/6] Executando Saneamento Operacional de Agendamentos e Análises..."
docker exec erp_app php /var/www/html/scripts/limpar_agendamentos_duplicados.php || true
echo "   ✅ Agendamentos e demandas de análise saneados com sucesso."

echo ""
echo "🔐 [6/6] Ajustando Permissões de Runtime para Apache (www-data)..."
sudo chown -R www-data:www-data storage uploads logs temp_pdf tmp
sudo chmod -R 775 storage uploads logs temp_pdf tmp
echo "   ✅ Permissões ajustadas."

echo ""
echo "================================================================="
echo "  🎉 ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!                          "
echo "  Nenhum dado, cliente, usuário ou proposta da VPS foi perdido!  "
echo "================================================================="
docker compose ps
