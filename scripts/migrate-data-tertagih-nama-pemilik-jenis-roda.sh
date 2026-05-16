#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

MIGRATION="database/migrations/2026_05_16_000001_add_nama_pemilik_jenis_roda_to_data_tertagih_table.php"

if [[ ! -f "$MIGRATION" ]]; then
  echo "Migration tidak ditemukan: $MIGRATION"
  exit 1
fi

echo "Menjalankan migration: $MIGRATION"
php artisan migrate --path="$MIGRATION" --force

echo "Selesai."
