#!/usr/bin/env bash
# Expose local WorldKeep HTTP MCP via cloudflared quick tunnel.
# Uses the unified Go server (MCP + REST on one port).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ADDR="${WORLDKEEP_HTTP_ADDR:-${WORLDKEEP_MCP_ADDR:-:8788}}"
LOG_DIR="${ROOT}/.tunnel"
mkdir -p "$LOG_DIR"

if ! command -v cloudflared >/dev/null 2>&1; then
  echo "cloudflared not found. Install: brew install cloudflared" >&2
  exit 1
fi

if [ ! -f "${WORLDKEEP_DATA_DIR:-$ROOT/data}/campaign_001.sqlite" ]; then
  echo "Seeding demo campaign..."
  (cd "$ROOT" && make seed)
fi

PORT="${ADDR##*:}"
LOCAL_URL="http://127.0.0.1:${PORT}"

echo "Starting unified WorldKeep HTTP (MCP + REST) on ${ADDR}…"
(
  cd "$ROOT/mcp"
  WORLDKEEP_DATA_DIR="${WORLDKEEP_DATA_DIR:-$ROOT/data}" \
    WORLDKEEP_HTTP_ADDR="$ADDR" \
    go run ./cmd/worldkeep-serve
) &
GO_PID=$!
trap 'kill $GO_PID 2>/dev/null || true' EXIT

for _ in $(seq 1 20); do
  if curl -sf "${LOCAL_URL}/healthz" >/dev/null 2>&1; then
    break
  fi
  sleep 0.25
done

echo "Opening tunnel to ${LOCAL_URL}…"
echo "  MCP endpoint:  \${TUNNEL_URL}/mcp"
echo "  REST API:      \${TUNNEL_URL}/api/v1"
echo ""
echo "For UI + MCP on one host in production, use: make docker-build"
cloudflared tunnel --url "$LOCAL_URL" 2>&1 | tee "$LOG_DIR/cloudflared.log"
