#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

export WORLDKEEP_DATA_DIR="${WORLDKEEP_DATA_DIR:-$ROOT/data}"
export WORLDKEEP_HTTP_ADDR="${WORLDKEEP_HTTP_ADDR:-:8788}"
export WORLDKEEP_CAMPAIGN_ID="${WORLDKEEP_CAMPAIGN_ID:-campaign_001}"

if [ ! -f "$WORLDKEEP_DATA_DIR/${WORLDKEEP_CAMPAIGN_ID}.sqlite" ]; then
  echo "Seeding demo campaign at $WORLDKEEP_DATA_DIR ..."
  make seed
fi

echo "Starting Go engine on ${WORLDKEEP_HTTP_ADDR} ..."
(
  cd mcp
  go run ./cmd/worldkeep-serve
) &
GO_PID=$!
cleanup() {
  kill "$GO_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

GO_PORT="${WORLDKEEP_HTTP_ADDR##*:}"
HEALTH_URL="http://127.0.0.1:${GO_PORT}/healthz"
for _ in $(seq 1 40); do
  if curl -sf "$HEALTH_URL" >/dev/null 2>&1; then
    break
  fi
  sleep 0.25
done

cd "$ROOT/web"
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --no-interaction
fi
php artisan migrate --force --no-interaction >/dev/null 2>&1 || php artisan migrate --force --no-interaction

WEB_PORT="${WORLDKEEP_WEB_PORT:-8000}"
echo ""
echo "Go engine (MCP + REST): http://127.0.0.1:${GO_PORT}"
echo "  MCP:  http://127.0.0.1:${GO_PORT}/mcp"
echo "  REST: http://127.0.0.1:${GO_PORT}/api/v1"
echo "Web UI: http://127.0.0.1:${WEB_PORT}/app"
echo ""

php artisan serve --host=127.0.0.1 --port="$WEB_PORT"
