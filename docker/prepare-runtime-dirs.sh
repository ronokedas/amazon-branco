#!/bin/sh
set -eu

APP_ROOT="${1:-/var/www/html}"

# O bind mount do projeto no VPS substitui as permissoes criadas durante o
# build da imagem. Estes caminhos precisam ser preparados a cada inicializacao.
RUNTIME_DIRS="
uploads
uploads/financeiro/comprovantes
uploads/assinaturas/propostas
logs
storage/backups
storage/certificados
storage/documentos_aprovados
storage/private
storage/private/assinaturas_responsaveis
storage/private/analises_planos
storage/private/documento_artefatos
storage/private/embarcacoes
storage/private/exportacoes
storage/private/vistorias
storage/sessions
tmp/pdfs
tmp/portal
temp_pdf
"

for relative_dir in $RUNTIME_DIRS; do
    absolute_dir="${APP_ROOT}/${relative_dir}"
    install -d -o www-data -g www-data -m 0770 "$absolute_dir"
done

# Corrige tambem arquivos e subpastas persistidos por uma versao anterior ou
# criados pelo usuario do host antes de o container iniciar.
for runtime_root in uploads logs storage tmp temp_pdf; do
    absolute_root="${APP_ROOT}/${runtime_root}"
    chown -R www-data:www-data "$absolute_root"
    find "$absolute_root" -type d -exec chmod 0770 {} \;
    find "$absolute_root" -type f -exec chmod 0660 {} \;
done

# Testar como o mesmo usuario dos processos PHP/worker. Um deploy com volume
# somente leitura ou filesystem incompatível deve falhar aqui, com erro claro.
if command -v su >/dev/null 2>&1; then
    for relative_dir in $RUNTIME_DIRS; do
        su -s /bin/sh www-data -c "test -w '${APP_ROOT}/${relative_dir}'" || {
            echo "ERRO: www-data nao possui permissao de escrita em ${APP_ROOT}/${relative_dir}" >&2
            exit 1
        }
    done
fi

echo "Diretorios gravaveis preparados para www-data."
