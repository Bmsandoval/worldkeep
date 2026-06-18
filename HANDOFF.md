# WorldKeep — Handoff

_Last updated: 2026-06-17_

**Starting point for a new session.** Read this first, then [AGENTS.md](./AGENTS.md) and the active GitHub issue.

**Repo:** [github.com/Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep) · **Branch:** `develop`

---

## Read this first (product)

Before implementing anything, read these docs in order:

1. [docs/product-thesis.md](./docs/product-thesis.md)
2. [docs/roadmap.md](./docs/roadmap.md)
3. [docs/entity-model.md](./docs/entity-model.md)
4. [docs/session-lifecycle.md](./docs/session-lifecycle.md)
5. [docs/context-pipeline.md](./docs/context-pipeline.md)
6. [docs/mcp.md](./docs/mcp.md)
7. [docs/poc.md](./docs/poc.md)

Full index: [docs/README.md](./docs/README.md)

---

## What we are building

We are **not** building a VTT, wiki, note-taking app, rules engine, or campaign simulator.

We **are** building:

```text
A continuity engine for AI-assisted campaigns.
```

The first version focuses exclusively on campaign memory and retrieval.

---

## Current development phase

**Active phase: POC** — see [docs/poc.md](./docs/poc.md)

Only implement functionality required to satisfy POC success criteria. Do not skip ahead to MVP, party system, world intel, ruleset engine, or living-world simulation.

**POC implementation complete through v0.6.0** — run live playtest checklist in [docs/playtest-notes.md](./docs/playtest-notes.md).

**POC success metric:** A campaign survives across multiple AI conversations without losing continuity.

---

## GitHub issue queue (POC-aligned)

All sub-issues **#23–#44** implemented on `develop` (PRs #46–#62). Parent release epics **#25–#45** track milestone acceptance.

| Milestone | Parent | Status |
| --------- | ------ | ------ |
| **v0.1.0** Manual world store | [#25](https://github.com/Bmsandoval/worldkeep/issues/25) | ✅ #23–#24 |
| **v0.2.0** MCP read (stdio) | [#30](https://github.com/Bmsandoval/worldkeep/issues/30) | ✅ #26–#29 |
| **v0.3.0** Propose/commit | [#34](https://github.com/Bmsandoval/worldkeep/issues/34) | ✅ #31–#33 |
| **v0.4.0** Session workflow | [#38](https://github.com/Bmsandoval/worldkeep/issues/38) | ✅ #35–#37 |
| **v0.5.0** Conflict + playtest | [#41](https://github.com/Bmsandoval/worldkeep/issues/41) | ✅ #39–#40 |
| **v0.6.0** ChatGPT tunnel | [#45](https://github.com/Bmsandoval/worldkeep/issues/45) | ✅ #42–#44 |

**Next:** live multi-chat playtest per [docs/playtest-notes.md](./docs/playtest-notes.md); then MVP planning ([docs/mvp.md](./docs/mvp.md)).

---

## Important: bootstrap planning superseded

Product direction lives in **`docs/`**. The June 2026 bootstrap (`docs/planning/`, Pokémon `start_campaign` / `move_to`) is **obsolete** — preserved in git history only.

**Before coding:** pick an open sub-issue from the table above; acceptance criteria in the issue body reference `docs/poc.md`.

---

## Repo layout

```text
worldkeep/
  HANDOFF.md              ← this file (session entry point)
  AGENTS.md               ← agent rules + stack constraints
  README.md               ← public one-pager
  ex.env                  ← env template (copy → local.env when code lands)
  data/                   ← campaign DB files (gitignored except .gitkeep)
  docs/                   ← product spec (source of truth)
  docs/workflow/          ← issue/PR process (not product spec)
  scripts/
    create_github_issues.py
    realign_github_issues.py
    tunnel.sh
  mcp/
    cmd/worldkeep-mcp/       ← stdio (Cursor)
    cmd/worldkeep-mcp-http/  ← HTTP (ChatGPT tunnel)
    internal/store/          ← SQLite campaign store
    internal/mcp/            ← MCP protocol + tools
```

---

## Git state

**Local `develop` head:** `d13e436` — POC product spec promoted to `docs/`.

No tags yet. No `main` branch (integration = `develop` only).

```bash
cd ~/projects/prototyper/prototypes/worldkeep
git pull origin develop
```

---

## What works today

| Area | Status |
| ---- | ------ |
| Product thesis, roadmap, entity model, MCP spec | ✅ [docs/](./docs/) |
| POC design (scope, schema, demo scenario) | ✅ [docs/poc.md](./docs/poc.md) |
| GitHub issues (v0.1.0–v0.6.0 POC queue) | ✅ implemented (#23–#44) |
| Go store + SQLite + Blackport seed | ✅ `make seed` / `make test` |
| MCP stdio server (Cursor) | ✅ `make mcp` |
| MCP HTTP + tunnel (ChatGPT) | ✅ `make mcp-http` / `scripts/tunnel.sh` |
| Live multi-chat playtest | 📋 [docs/playtest-notes.md](./docs/playtest-notes.md) |

---

## Agreed stack (prototype)

| Area | Choice |
| ---- | ------ |
| Language | **Go** |
| Storage | **SQLite** (Postgres optional for hosted later) — see [docs/poc.md](./docs/poc.md) §11 |
| MCP (Cursor) | stdio |
| MCP (ChatGPT) | Streamable HTTP + tunnel (after POC read path works) |
| Auth | None (local single-user) |

**Reference:** MCP HTTP shape → [timelord/mcp](https://github.com/Bmsandoval/timelord/tree/feat/chatgpt-mcp/mcp)

---

## First deliverable (POC)

Build the smallest system that can:

**Store:** campaigns, actors, locations, events, facts, plots

**Retrieve:** entity lookup, search, campaign overview

**Context:** `compile_scene_context()`

**Updates:** `propose_world_update()` → `commit_world_update()`

Details: [docs/poc.md](./docs/poc.md) §7–§14. Demo scenario: *Shadows of Blackport* (Finn, Crimson Guild, Missing Prince).

---

## New session quick start

1. Read this file + [AGENTS.md](./AGENTS.md) + [docs/poc.md](./docs/poc.md).
2. Run `make test && make seed` — connect MCP per [docs/playtest-notes.md](./docs/playtest-notes.md) or [docs/chatgpt-mcp-setup.md](./docs/chatgpt-mcp-setup.md).
3. Branch from `develop`; implement only agreed scope.
4. `cd mcp && go test ./...` before PR.
5. Open PR to `develop`; **do not merge** unless maintainer explicitly asks.

Process: [docs/workflow/prototype-workflow.md](./docs/workflow/prototype-workflow.md).

---

## Avoid premature features

Do **not** build yet: ruleset engine, narrative optimization, living world simulation, faction/economy simulation, multi-agent systems. See [docs/roadmap.md](./docs/roadmap.md).

---

## Maintainer gates

- **Never merge PRs** unless explicitly asked.
- **Never cut releases/tags** unless explicitly asked.
- **Never implement from docs alone** — need an open, agreed sub-issue.
- **`docs/` wins** over bootstrap planning in git history.
