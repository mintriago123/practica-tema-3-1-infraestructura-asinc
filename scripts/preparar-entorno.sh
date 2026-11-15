#!/usr/bin/env bash
set -euo pipefail

PRACTICA_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_SOURCE="${NEXO_BACKEND_DIR:-${PRACTICA_DIR}/../NexoCommerce/backend}"
RUNTIME_BACKEND="${PRACTICA_DIR}/runtime/backend"

if [[ ! -f "${BACKEND_SOURCE}/artisan" ]]; then
  echo "No encuentro el backend Laravel en: ${BACKEND_SOURCE}" >&2
  echo "Define NEXO_BACKEND_DIR con la ruta de NexoCommerce/backend." >&2
  exit 1
fi
if [[ -e "${RUNTIME_BACKEND}" ]]; then
  echo "Ya existe ${RUNTIME_BACKEND}; no lo sobrescribo." >&2
  exit 1
fi

python3 - "${BACKEND_SOURCE}" "${RUNTIME_BACKEND}" "${PRACTICA_DIR}" <<'PY'
from pathlib import Path
from shutil import copytree, ignore_patterns, copy2
import sys
src, dst, practica = map(Path, sys.argv[1:])
copytree(src, dst, ignore=ignore_patterns('.env', '.env.*', '.git', 'node_modules'))
for rel in ('app/Practica31',):
    target=dst/'app'/'Practica31'
    copytree(practica/'codigo/app/Practica31', target)
copy2(practica/'codigo/config/practica31.php', dst/'config/practica31.php')
copy2(practica/'codigo/routes/api-practica.php', dst/'routes/api-practica.php')
fixture=dst/'practice-data'/'pedidos-demo.json'
fixture.parent.mkdir(parents=True,exist_ok=True)
copy2(practica/'codigo/practice-data/pedidos-demo.json',fixture)
routes=dst/'routes/api.php'
text=routes.read_text(encoding='utf-8')
marker="require __DIR__.'/api-practica.php';"
if marker not in text:
    routes.write_text(text.rstrip()+"\n\n"+marker+"\n",encoding='utf-8')
bootstrap=dst/'bootstrap'/'app.php'
text=bootstrap.read_text(encoding='utf-8')
command_registration="    ->withCommands([__DIR__.'/../app/Practica31/Console/Commands'])\n"
if command_registration.strip() not in text:
    text=text.replace("    ->withMiddleware(", command_registration+"    ->withMiddleware(", 1)
    bootstrap.write_text(text,encoding='utf-8')
dockerfile=dst/'Dockerfile'
text=dockerfile.read_text(encoding='utf-8')
text=text.replace("--no-progress\n", "--no-progress --ignore-platform-req=ext-sockets\n", 1)
text=text.replace("--no-interaction\n\n\nFROM php:", "--no-interaction --ignore-platform-req=ext-sockets\n\n\nFROM php:", 1)
text=text.replace("pgsql intl zip bcmath opcache \\", "pgsql intl zip bcmath opcache sockets \\", 1)
dockerfile.write_text(text,encoding='utf-8')
PY

mkdir -p "${PRACTICA_DIR}/runtime"
docker run --rm --mount "type=bind,src=${RUNTIME_BACKEND},dst=/app" --workdir /app composer:2 \
  require 'php-amqplib/php-amqplib:^3.7' --ignore-platform-req=ext-sockets --no-interaction --no-progress

echo "Módulo preparado en ${RUNTIME_BACKEND}; backend original sin cambios."
