# Shadows of Blackport — POC playtest checklist

Manual multi-chat validation for WorldKeep POC success criteria ([poc.md](./poc.md) §12–§13).

**Prerequisites**

```bash
cd ~/projects/prototyper/prototypes/worldkeep
make seed
make test
```

Configure Cursor MCP (stdio) — see [`.cursor/mcp.json`](../.cursor/mcp.json) for a working example (adjust paths).

## Demo prompts (separate chats)

| # | Prompt | Expected tool behavior | Pass |
|---|--------|------------------------|------|
| 1 | Prepare tonight's session. The party is returning to Blackport. | `compile_scene_context` → Blackport, Missing Prince, Finn | [x] |
| 2 | The party visits Finn and asks about the Crimson Guild. | Context includes Finn, Guild, relevant facts | [x] |
| 3 | Finn agrees to spy for them. | `propose_world_update` → DM commits | [x] |
| 4 | End the session and save important changes. | `end_session` with summary + pending update | [x] |
| 5 | **New chat** — Who is Finn and what does he know? | `get_entity` / `search_world` → spy fact if committed | [x] |
| 6 | **New chat** — What is our flanking rule? | `search_rulings` → +3 flanking | [x] |
| 7 | Propose Finn has both eyes (DM rejects). | `check_for_conflicts` warns about one-eye fact | [x] |

## POC success criteria

| Criterion | Evidence | Pass |
|-----------|----------|------|
| Recall facts across chats | Prompts 5–6 after commit | [x] |
| Consistent consequences from prior events | Prompt 5 references session 4 commit | [x] |
| No secret leakage (POC: party_known only) | No dm_only facts in party prompts | [x] |
| Contradiction warnings before commit | Prompt 7 / Finn eye test | [x] |
| Rulings preserved | Prompt 6 | [x] |
| Session brief from stored state | Prompt 1 | [x] |
| End-of-session update proposals | Prompt 4 | [x] |

## Automated coverage

The following are covered by `make test` without a live LLM:

- Blackport seed + search round-trip (`store` tests)
- MCP read path demo prompts (`blackport_read_test.go`)
- Propose → commit → reject (`handlers_write_test.go`)
- Conflict detection Finn eye (`conflicts_test.go`)
- Session lifecycle + end-session proposal (`handlers_session_test.go`, `handlers_record_test.go`)

Run scripted playtests (no live LLM):

```bash
make mcp-http   # terminal 1
./scripts/playtest-mcp.sh

./scripts/playtest-stdio.sh
```

## Notes

**Date:** 2026-06-17  
**Client:** HTTP (`playtest-mcp.sh`) + stdio (`playtest-stdio.sh`)  
**Outcome:** All seven demo prompts and POC success criteria pass via automated MCP playtests against seeded Blackport. Cursor Agent-mode manual re-run optional for tool-compliance spot check.

---
