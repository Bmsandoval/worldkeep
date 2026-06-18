#!/usr/bin/env bash
# POC playtest over MCP stdio — fresh seeded DB, same checklist as playtest-mcp.sh.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MCP="$ROOT/mcp"
DATA=$(mktemp -d)
trap 'rm -rf "$DATA"' EXIT

export WORLDKEEP_DATA_DIR="$DATA"
(cd "$MCP" && go run ./cmd/seed >/dev/null 2>&1)

mcp_call() {
  local id=$1 name=$2 args=$3
  local params
  params=$(jq -nc --arg n "$name" --argjson a "$args" '{name:$n, arguments:$a}')
  {
    printf '%s\n' '{"jsonrpc":"2.0","id":0,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"playtest","version":"1"}}}'
    printf '%s\n' '{"jsonrpc":"2.0","method":"notifications/initialized"}'
    printf '%s\n' "{\"jsonrpc\":\"2.0\",\"id\":$id,\"method\":\"tools/call\",\"params\":$params}"
  } | (cd "$MCP" && go run ./cmd/worldkeep-mcp 2>/dev/null) | jq -s '.[-1]'
}

text() { jq -r '.result.content[0].text // empty'; }

echo "=== stdio MCP playtest (fresh Blackport seed) ==="

R1=$(mcp_call 1 compile_scene_context '{"prompt":"Prepare for tonight'\''s session. The party is returning to Blackport."}')
echo "$R1" | text | jq -e '(.plots | length) > 0 and (.actors | length) > 0 and (.locations | length) > 0' >/dev/null
echo "PASS prompt 1 — scene context"

R2=$(mcp_call 2 start_session '{"title":"Session 3"}')
SESSION=$(echo "$R2" | text | jq -r '.id')
echo "PASS start_session ($SESSION)"

R3=$(mcp_call 3 propose_world_update "$(jq -nc \
  --arg reason "Finn spy" \
  '{reason:$reason, changes:[{op:"add_fact", fact:{entity_id:"npc_finn", text:"Finn agreed to spy on the Crimson Guild for the party.", visibility:"party_known", confidence:"high"}}]}')")
UPDATE=$(echo "$R3" | text | jq -r '.pending_update.id')
echo "PASS propose_world_update ($UPDATE)"

R4=$(mcp_call 4 commit_world_update "$(jq -nc --arg id "$UPDATE" '{update_id:$id}')")
echo "$R4" | text | jq -e '.status == "committed"' >/dev/null
echo "PASS commit_world_update"

R5=$(mcp_call 5 end_session "$(jq -nc \
  --arg sid "$SESSION" \
  --arg summary "Party recruited Finn." \
  '{session_id:$sid, summary:$summary}')")
echo "$R5" | text | jq -e '.session.status == "closed"' >/dev/null
echo "PASS end_session"

R6=$(mcp_call 6 search_world '{"query":"spy"}')
echo "$R6" | text | jq -e '.facts[0].text | contains("spy")' >/dev/null
echo "PASS prompt 5 — spy fact recall"

R7=$(mcp_call 7 search_rulings '{"query":"flanking"}')
echo "$R7" | text | jq -e '.[0].answer | contains("+3")' >/dev/null
echo "PASS prompt 6 — flanking ruling"

R8=$(mcp_call 8 check_for_conflicts "$(jq -nc \
  '{changes:[{op:"add_fact", fact:{entity_id:"npc_finn", text:"Finn has both eyes.", visibility:"party_known", confidence:"high"}}]}')")
echo "$R8" | text | jq -e '.warnings[0].type == "possible_contradiction"' >/dev/null
echo "PASS prompt 7 — contradiction warning"

echo
echo "=== All stdio playtest checks passed ==="
