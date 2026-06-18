# Shadows of Blackport — POC playtest checklist

Manual multi-chat validation for WorldKeep POC success criteria ([poc.md](./poc.md) §12–§13).

**Prerequisites**

```bash
cd ~/projects/prototyper/prototypes/worldkeep
make seed
make test
```

Configure Cursor MCP (stdio):

```json
{
  "mcpServers": {
    "worldkeep": {
      "command": "go",
      "args": ["run", "./cmd/worldkeep-mcp"],
      "cwd": "/path/to/worldkeep/mcp",
      "env": {
        "WORLDKEEP_DATA_DIR": "/path/to/worldkeep/data"
      }
    }
  }
}
```

## Demo prompts (separate chats)

| # | Prompt | Expected tool behavior | Pass |
|---|--------|------------------------|------|
| 1 | Prepare tonight's session. The party is returning to Blackport. | `compile_scene_context` → Blackport, Missing Prince, Finn | [ ] |
| 2 | The party visits Finn and asks about the Crimson Guild. | Context includes Finn, Guild, relevant facts | [ ] |
| 3 | Finn agrees to spy for them. | `propose_world_update` → DM commits | [ ] |
| 4 | End the session and save important changes. | `end_session` with summary + pending update | [ ] |
| 5 | **New chat** — Who is Finn and what does he know? | `get_entity` / `search_world` → spy fact if committed | [ ] |
| 6 | **New chat** — What is our flanking rule? | `search_rulings` → +3 flanking | [ ] |
| 7 | Propose Finn has both eyes (DM rejects). | `check_for_conflicts` warns about one-eye fact | [ ] |

## POC success criteria

| Criterion | Evidence | Pass |
|-----------|----------|------|
| Recall facts across chats | Prompts 5–6 after commit | [ ] |
| Consistent consequences from prior events | Prompt 5 references session 4 commit | [ ] |
| No secret leakage (POC: party_known only) | No dm_only facts in party prompts | [ ] |
| Contradiction warnings before commit | Prompt 7 / Finn eye test | [ ] |
| Rulings preserved | Prompt 6 | [ ] |
| Session brief from stored state | Prompt 1 | [ ] |
| End-of-session update proposals | Prompt 4 | [ ] |

## Automated coverage

The following are covered by `make test` without a live LLM:

- Blackport seed + search round-trip (`store` tests)
- MCP read path demo prompts (`blackport_read_test.go`)
- Propose → commit → reject (`handlers_write_test.go`)
- Conflict detection Finn eye (`conflicts_test.go`)
- Session lifecycle + end-session proposal (`handlers_session_test.go`, `handlers_record_test.go`)

## Notes

_Record findings from live Cursor/ChatGPT sessions below._

---

**Date:**  
**Client:** Cursor / ChatGPT  
**Outcome:**  
