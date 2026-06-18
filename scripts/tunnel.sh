#!/usr/bin/env bash
# Expose local WorldKeep HTTP MCP via cloudflared quick tunnel.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ADDR="${WORLDKEEP_MCP_ADDR:-:8788}"
LOG_DIR="${ROOT}/.tunnel"
mkdir -p "$LOG_DIR"

if ! command -v cloudflared >/dev/null 2>&1; then
  echo "cloudflared not found. Install: brew install cloudflared" >&2
  exit 1
fi

echo "Starting WorldKeep HTTP MCP on ${ADDR}…"
(
  cd "$ROOT/mcp"
  WORLDKEEP_DATA_DIR="${ROOT}/data" WORLDKEEP_MCP_ADDR="$ADDR" go run ./cmd/worldkeep-mcp-http
) &
MCP_PID=$!
trap 'kill $MCP_PID 2>/dev/null || true' EXIT

sleep 2

echo "Opening tunnel to http://127.0.0.1${ADDR/:/}…"
cloudflared tunnel --url "http://127.0.0.1${ADDR/:/}" 2>&1 | tee "$LOG_DIR/cloudflared.log"
