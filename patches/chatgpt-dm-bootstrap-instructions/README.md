# ChatGPT DM bootstrap + MCP instructions (split-out diff)

Saved so another agent can work on `develop` without conflict. Re-apply when ready.

## Contents

| File | Change |
|------|--------|
| `web/app/Services/WorldKeep/Mcp/Instructions.php` | CRITICAL bootstrap/resume sections in MCP `instructions` |
| `web/app/Services/WorldKeep/Mcp/ToolDefinitions.php` | Tool blurbs for start_session, record_event, propose/commit, import |
| `AGENTS.md` | Hard rules summary + link to bootstrap doc |
| `docs/chatgpt-dm-bootstrap.md` | Maintainer doc (why record_event alone is not enough) |
| `web/tests/Unit/Services/WorldKeep/Mcp/InstructionsTest.php` | Unit test for instruction keywords |

## Apply

From repo root (`worldkeep/`):

```bash
git apply --check patches/chatgpt-dm-bootstrap-instructions/chatgpt-dm-bootstrap-instructions.patch
git apply patches/chatgpt-dm-bootstrap-instructions/chatgpt-dm-bootstrap-instructions.patch
cd web && php artisan test --filter=InstructionsTest
```

If paths conflict after other merges, apply with `git apply -3` or re-apply manually from the patch.

## Context

User saw only a Recent Events line after "start new campaign" because ChatGPT called `record_event` without `start_session` + `propose_world_update` + `commit_world_update`. This diff adds system-level MCP guidance to fix that.

Not included: MCP OAuth (already on `develop` as v1.9.1), prod deploy, multi-campaign support.
