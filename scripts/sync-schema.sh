#!/usr/bin/env bash
# Copia lo schema canonico (/shared) nella cartella del plugin backend.
# Il frontend lo importa direttamente via alias @shared; il backend ne ha
# bisogno come file proprio per il deploy come plugin WordPress.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cp "$ROOT/shared/form-schema.json" "$ROOT/backend/formedil-moduli/schema/form-schema.json"
echo "Schema sincronizzato -> backend/formedil-moduli/schema/form-schema.json"
