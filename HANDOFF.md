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

**POC success metric:** A campaign survives across multiple AI conversations without losing continuity.

---

## Important: planning superseded

Product direction now lives in **`docs/`** (promoted from the `gpt/` planning session).

The **June 2026 bootstrap** (`docs/planning/`, Pokémon `start_campaign` / `move_to` tools, v0.1–v0.4 milestone structure) is **obsolete**. GitHub issues [#4–#22](https://github.com/Bmsandoval/worldkeep/issues) still reflect that old plan.

**Before coding:** align with the maintainer on whether to close/reopen issues against [docs/poc.md](./docs/poc.md) and [docs/mcp.md](./docs/mcp.md). Do not implement stale issue acceptance criteria without confirmation.

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
  mcp/                    ← (planned) MCP server — empty today
```

---

## Git state

**`origin/develop` head:** `43f4b16` — "Add HANDOFF.md as session starting point for v0.1 work."

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
| GitHub issues (v0.0.0–v0.4.0 structure) | ⚠️ stale — needs realignment to POC docs |
| Go MCP server | ❌ not started |
| SQLite / campaigns | ❌ not started |

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
2. Confirm active issue with maintainer — **do not assume #4 is still valid**.
3. Branch from `develop`; implement only agreed scope.
4. `go test ./...` before PR (once Go code exists).
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
- **`docs/` wins** over stale GitHub issues and over bootstrap planning in git history.
