#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="${SOURCE_DIR:-/var/www/lawyers}"
BACKUP_ROOT="${BACKUP_ROOT:-/root/lawyers-prod-backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
DEST_DIR="$BACKUP_ROOT/$STAMP"
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

mkdir -p "$BACKUP_ROOT"

RSYNC_ARGS=(
  -a
  --itemize-changes
  --exclude '.git/'
  --exclude 'vendor/'
  --exclude 'node_modules/'
  --exclude 'tmp/'
  --exclude 'cache/'
  --exclude 'sessions/'
  --exclude 'public/tmp-downloads/'
  --exclude 'public/codigos_completos.txt'
  --exclude 'public/data/*.txt'
  --exclude 'PROJECT_ROADMAP_UX_UI.md'
  --exclude '*.bak'
  --exclude '*.bak_*'
  --exclude '*.backup'
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
  echo "Modo simulacion. No se escribira nada en $DEST_DIR"
else
  echo "Respaldando produccion en $DEST_DIR"
  mkdir -p "$DEST_DIR"
fi

rsync "${RSYNC_ARGS[@]}" "$SOURCE_DIR"/ "$DEST_DIR"/

cat <<EOF

Respaldo completado.
Origen: $SOURCE_DIR
Destino: $DEST_DIR
EOF
