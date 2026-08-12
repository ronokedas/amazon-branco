#!/bin/sh
# Restaura um pacote de recovery em uma clonagem nova. Nunca substitui dados
# persistentes ja existentes sem a confirmacao --forcar.
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PACKAGE="${1:-$ROOT/recovery/amazon-recovery-latest.enc}"
MANIFEST="${PACKAGE%.enc}.manifest.json"
FORCAR="${2:-}"

[ -n "${RECOVERY_BACKUP_PASSWORD:-}" ] || { echo 'Defina RECOVERY_BACKUP_PASSWORD.' >&2; exit 1; }
[ -s "$PACKAGE" ] && [ -s "$MANIFEST" ] || { echo 'Pacote ou manifesto ausente.' >&2; exit 1; }

EXPECTED=$(sed -n 's/.*"sha256": "\([0-9a-f]*\)".*/\1/p' "$MANIFEST" | head -n 1)
ACTUAL=$(sha256sum "$PACKAGE" | awk '{print $1}')
[ "$EXPECTED" = "$ACTUAL" ] || { echo 'Hash do pacote invalido.' >&2; exit 1; }

for path in .env db.sql storage uploads minio-data; do
    if [ -e "$ROOT/$path" ] && [ "$path" != 'db.sql' ] && [ "$FORCAR" != '--forcar' ]; then
        echo "Destino ja possui $path. Use uma clonagem nova ou --forcar." >&2; exit 1
    fi
done

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
openssl enc -d -aes-256-cbc -pbkdf2 -iter 600000 -in "$PACKAGE" -out "$WORK/recovery.zip" -pass env:RECOVERY_BACKUP_PASSWORD
unzip -tq "$WORK/recovery.zip"
unzip -q "$WORK/recovery.zip" -d "$WORK/conteudo"
for path in .env db.sql storage uploads minio-data; do [ -e "$WORK/conteudo/$path" ] || { echo "Componente ausente: $path" >&2; exit 1; }; done

cp "$WORK/conteudo/.env" "$ROOT/.env"
cp "$WORK/conteudo/db.sql" "$ROOT/db.sql"
for path in storage uploads minio-data; do rm -rf "$ROOT/$path"; cp -a "$WORK/conteudo/$path" "$ROOT/$path"; done
echo 'Recuperacao concluida. Em uma VPS nova, execute: docker compose up -d --build'
