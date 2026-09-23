#!/bin/bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
/usr/bin/php "$ROOT/tocron/import_impostazioni.php"