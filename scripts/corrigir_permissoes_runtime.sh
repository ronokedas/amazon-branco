#!/bin/sh
# Execute somente em manutencao, quando uma restauracao trouxe arquivos com
# dono/permissoes incorretos. Nao e chamado no boot da aplicacao.
set -eu

APP_ROOT="${1:-/var/www/html}"

for runtime_root in uploads logs storage tmp temp_pdf; do
    absolute_root="${APP_ROOT}/${runtime_root}"
    [ -d "$absolute_root" ] || continue
    chown -R www-data:www-data "$absolute_root"
    find "$absolute_root" -type d -exec chmod 0770 {} +
    find "$absolute_root" -type f -exec chmod 0660 {} +
done

echo "Permissoes do runtime corrigidas."
