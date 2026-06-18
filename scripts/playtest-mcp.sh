#!/usr/bin/env bash
# Local MCP playtest — JSON-RPC over HTTP like ChatGPT would use.
set -euo pipefail

BASE="${1:-http://127.0.0.1:8788/mcp}"
ID=1

rpc() {
  local method=$1
  local params=${2:-{}}
  ID=$((ID + 1))
  curl -sS "$BASE" -H 'Content-Type: application/json' \
    -d "{\"jsonrpc\":\"2.0\",\"id\":$ID,\"method\":\"$method\",\"params\":$params}"
}

call() {
  local name=$1
  local args=$2
  ID=$((ID + 1))
  local params
  params=$(jq -nc --arg n "$name" --argjson a "$args" \
    '{name:$n, arguments:$a}')
  curl -sS "$BASE" -H 'Content-Type: application/json' \
    -d "{\"jsonrpc\":\"2.0\",\"id\":$ID,\"method\":\"tools/call\",\"params\":$params}"
}

section() { echo; echo "=== $* ==="; }

section "Health"
curl -sS "http://127.0.0.1:8788/healthz"
echo

section "Initialize"
rpc initialize '{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"playtest","version":"1.0"}}' \
  | jq -r '.result.instructions' | head -5

section "Prompt 1: Prepare session — party returning to Blackport"
call compile_scene_context '{"prompt":"Prepare for tonight'\''s session. The party is returning to Blackport."}' \
  | jq -r '.result.content[0].text' | head -40

section "Start session"
SESSION=$(call start_session '{"title":"Session 3 — Finn informant"}' | jq -r '.result.content[0].text')
echo "$SESSION" | jq -r '.id // empty'
SESSION_ID=$(echo "$SESSION" | jq -r '.id')

section "Prompt 2: Party visits Finn about Crimson Guild"
call compile_scene_context '{"prompt":"The party visits Finn and asks about the Crimson Guild."}' \
  | jq -r '.result.content[0].text' | jq '{actors: [.actors[]?.name], facts: [.facts[]?.text], plots: [.plots[]?.name]}'

section "Prompt 3: Finn agrees to spy — propose update"
PROPOSE=$(call propose_world_update "$(jq -nc \
  --arg reason "Finn recruited as informant" \
  '{reason:$reason, changes:[{op:"add_fact", fact:{entity_id:"npc_finn", text:"Finn agreed to spy on the Crimson Guild for the party.", visibility:"party_known", confidence:"high"}}]}')")
echo "$PROPOSE" | jq -r '.result.content[0].text' | jq '{update: .pending_update.id, warnings: .warnings}'
UPDATE_ID=$(echo "$PROPOSE" | jq -r '.result.content[0].text | fromjson | .pending_update.id')

section "DM approves — commit_world_update"
call commit_world_update "$(jq -nc --arg id "$UPDATE_ID" '{update_id:$id}')" \
  | jq -r '.result.content[0].text' | jq '{status, id}'

section "Prompt 4: End session"
call end_session "$(jq -nc \
  --arg sid "$SESSION_ID" \
  --arg summary "Party recruited Finn; learned Crimson Guild controls the docks." \
  '{session_id:$sid, summary:$summary}')" \
  | jq -r '.result.content[0].text' | jq '{session: .session.status, summary: .session_summary}'

section "Prompt 5 (NEW CHAT): Who is Finn?"
call get_entity '{"entity_id":"npc_finn"}' \
  | jq -r '.result.content[0].text' | jq '{name, summary, type}'
call search_world '{"query":"Finn spy"}' \
  | jq -r '.result.content[0].text' | jq '.facts[]?.text'

section "Prompt 6 (NEW CHAT): Flanking rule?"
call search_rulings '{"query":"flanking"}' \
  | jq -r '.result.content[0].text' | jq '.[] | {question, answer}'

section "Prompt 7: Contradiction — Finn has both eyes"
call check_for_conflicts "$(jq -nc \
  '{changes:[{op:"add_fact", fact:{entity_id:"npc_finn", text:"Finn has both eyes.", visibility:"party_known", confidence:"high"}}]}')" \
  | jq -r '.result.content[0].text'

echo
echo "=== Playtest script complete ==="
