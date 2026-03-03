#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="${SOURCE_DIR:-/root/lawyers-open-source}"
TARGET_DIR="${TARGET_DIR:-/var/www/lawyers}"
DRY_RUN=1

if [[ "${1:-}" == "--apply" ]]; then
  DRY_RUN=0
elif [[ "${1:-}" != "" ]]; then
  echo "Uso: $0 [--apply]"
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "rsync no esta instalado."
  exit 1
fi

if [[ ! -d "$SOURCE_DIR" ]]; then
  echo "No existe SOURCE_DIR: $SOURCE_DIR"
  exit 1
fi

if [[ ! -d "$TARGET_DIR" ]]; then
  echo "No existe TARGET_DIR: $TARGET_DIR"
  exit 1
fi

RSYNC_ARGS=(
  -a
  --delete
  --itemize-changes
  --exclude '.git/'
  --exclude 'vendor/'
  --exclude '.env'
  --exclude '.env.*'
  --exclude 'public/tmp-downloads/'
  --exclude 'public/codigos_completos.txt'
  --exclude 'public/data/*.txt'
  --exclude '*.bak'
  --exclude '*.bak_*'
  --exclude '*.backup'
  --exclude '*.tmp'
  --exclude '*.temp'
  --exclude '*.swp'
  --exclude '*.swo'
  --exclude '*~'
  --exclude '*.log'
  --exclude '*.sql'
  --exclude '*.sqlite'
  --exclude '*.sqlite3'
  --exclude '*.dump'
  --exclude '*.csv'
  --exclude '*.zip'
)

if [[ "$DRY_RUN" -eq 1 ]]; then
  RSYNC_ARGS+=(--dry-run)
  echo "Modo simulacion. No se escribira nada en $TARGET_DIR"
else
  echo "Aplicando deploy hacia $TARGET_DIR"
fi

rsync "${RSYNC_ARGS[@]}" "$SOURCE_DIR"/ "$TARGET_DIR"/

cat <<EOF

Siguientes pasos recomendados:
1. composer install --no-dev en el destino si cambian dependencias.
2. php -d memory_limit=256M -l $TARGET_DIR/public/index.php
3. apache2ctl -t
4. systemctl reload apache2
5. Probar home, admin y login Google.
EOF
