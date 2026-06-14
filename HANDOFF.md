# Worldkeep — Handoff

_Last updated: 2026-06-14_

**Starting point for a new session.** Read this first, then [AGENTS.md](./AGENTS.md) and the active GitHub issue.

Worldkeep is a **local-first MCP server** that stores fictional world state (map, NPCs, events, lore, player position) so ChatGPT or Cursor can **read canon before narrating** and **write facts as play progresses** — instead of forgetting after a long session.

**Repo:** [github.com/Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep) · **Branch:** `develop`

---

## Current phase

**Planning complete (`v0.0.0`). No application code yet.**

Next work is **v0.1.0 — Core MCP + SQLite** ([#10](https://github.com/Bmsandoval/worldkeep/issues/10)). First implementable slice: [#4 Go module, SQLite schema, and migrations](https://github.com/Bmsandoval/worldkeep/issues/4).

Closed in v0.0.0:

- [#1](https://github.com/Bmsandoval/worldkeep/issues/1) — planning docs + AGENTS
- [#2](https://github.com/Bmsandoval/worldkeep/issues/2) — milestones + release epics

Open parent [#3](https://github.com/Bmsandoval/worldkeep/issues/3) can close when maintainer tags `v0.0.0`.

---

## Repo layout

```text
worldkeep/
  HANDOFF.md              ← this file (session entry point)
  AGENTS.md               ← agent rules + stack constraints
  README.md               ← public one-pager
  ex.env                  ← env template (copy → local.env when code lands)
  data/                   ← campaign SQLite files (gitignored except .gitkeep)
  docs/planning/          ← all strategy + design (see index below)
  scripts/
    create_github_issues.py
  mcp/                    ← (planned) Go MCP server — empty today
```

**Canonical planning index:** [docs/planning/README.md](./docs/planning/README.md)

---

## Git state

**`origin/develop` head:** `eff38a1` — "Link prototype release backlog to GitHub issue numbers."

Prior commit: `ae20a2b` — bootstrap planning + workflow.

No tags yet. No `main` branch (integration = `develop` only).

```bash
cd ~/projects/prototyper/prototypes/worldkeep
git pull origin develop
```

---

## What works today

| Area | Status |
|------|--------|
| Product vision + full expansion roadmap | ✅ [full-expansion-roadmap.md](./docs/planning/full-expansion-roadmap.md) |
| Scenario bootstrap design ("we are pokemon") | ✅ [scenario-bootstrap.md](./docs/planning/scenario-bootstrap.md) |
| MCP tool + SQLite schema design | ✅ [mcp-tools-design.md](./docs/planning/mcp-tools-design.md) |
| Local + tunnel architecture | ✅ [local-mcp-architecture.md](./docs/planning/local-mcp-architecture.md) |
| GitHub milestones v0.0.0–v0.4.0 + 22 issues | ✅ [prototype-release-backlog.md](./docs/planning/prototype-release-backlog.md) |
| Go MCP server | ❌ not started |
| SQLite / campaigns | ❌ not started |
| ChatGPT tunnel path | ❌ v0.2 ([#14](https://github.com/Bmsandoval/worldkeep/issues/14)) |

---

## Core idea (don't lose this)

1. User **dictates a premise** — e.g. *"We are Pokémon trainers starting in Pallet Town."*
2. MCP tool **`start_campaign(name, premise)`** creates a SQLite campaign and stores premise as **critical lore**.
3. During play, the LLM calls **`get_scene_context`** before narrating and **write tools** when facts change (`move_to`, `upsert_entity`, `add_lore`, …).
4. Worldkeep returns **raw JSON**; the LLM narrates. The server never generates story text.
5. User closes ChatGPT, reopens tomorrow — **canon persists** on disk.

ChatGPT connects via **local HTTP + cloudflared tunnel** in v0.2 (no cloud deploy in prototype). Cursor uses **stdio MCP** in v0.1.

---

## Agreed stack (prototype)

| Area | Choice |
|------|--------|
| Language | **Go** |
| Storage | **SQLite + FTS5**, one file per campaign under `./data/` |
| MCP (Cursor) | stdio |
| MCP (ChatGPT) | Streamable HTTP on `:8788` + tunnel (v0.2) |
| Auth | None (local single-user) |

**Reference implementations:**

- MCP HTTP shape → [timelord/mcp](https://github.com/Bmsandoval/timelord/tree/feat/chatgpt-mcp/mcp)
- SQLite FTS pattern → prototyper `ideator/data/idea-ledger.sqlite` (concept only)

---

## Issue queue (open)

```bash
gh issue list --repo Bmsandoval/worldkeep --state open
```

| Milestone | Parent | Theme |
|-----------|--------|--------|
| v0.0.0 | [#3](https://github.com/Bmsandoval/worldkeep/issues/3) | Planning (wrap up / tag) |
| **v0.1.0** | [**#10**](https://github.com/Bmsandoval/worldkeep/issues/10) | **Core MCP + SQLite — START HERE** |
| v0.2.0 | [#14](https://github.com/Bmsandoval/worldkeep/issues/14) | ChatGPT via tunnel |
| v0.3.0 | [#18](https://github.com/Bmsandoval/worldkeep/issues/18) | Templates + export |
| v0.4.0 | [#22](https://github.com/Bmsandoval/worldkeep/issues/22) | Hardening |

### v0.1.0 sub-issues (implement in order)

| # | Title |
|---|--------|
| [#4](https://github.com/Bmsandoval/worldkeep/issues/4) | Go module, SQLite schema, and migrations |
| [#5](https://github.com/Bmsandoval/worldkeep/issues/5) | stdio MCP server with server instructions |
| [#6](https://github.com/Bmsandoval/worldkeep/issues/6) | Campaign lifecycle tools |
| [#7](https://github.com/Bmsandoval/worldkeep/issues/7) | Read tools |
| [#8](https://github.com/Bmsandoval/worldkeep/issues/8) | Write tools |
| [#9](https://github.com/Bmsandoval/worldkeep/issues/9) | Integration test (Pokémon premise round-trip) |

---

## New session quick start

1. Read this file + [AGENTS.md](./AGENTS.md).
2. Confirm active issue with maintainer — default: **#4**.
3. Branch from `develop`:

```bash
cd ~/projects/prototyper/prototypes/worldkeep
gh issue develop 4 --name issue-4-go-schema --checkout --base develop
```

4. Implement **only** that issue's acceptance criteria.
5. `go test ./...` before PR (once Go code exists).
6. Open PR to `develop` with `- Resolves Bmsandoval/worldkeep#4` as first line.
7. **Do not merge** unless maintainer explicitly asks.

Full process: [docs/planning/prototype-workflow.md](./docs/planning/prototype-workflow.md).

---

## v0.1.0 implementation sketch

Planned layout (from [local-mcp-architecture.md](./docs/planning/local-mcp-architecture.md)):

```text
cmd/worldkeep-mcp/main.go
internal/
  domain/          # Campaign, Location, Entity, Event, Lore, PlayerState
  store/           # SQLite repos + FTS + migrations
  mcp/             # tool defs, handlers, stdio transport
```

Schema and tool names: [mcp-tools-design.md](./docs/planning/mcp-tools-design.md).

**Integration test bar (#9):** `start_campaign` with premise *"we are Pokémon trainers"* → `move_to` → `upsert_entity` → `search_world` — no live LLM.

---

## Timeline (recommended)

From [full-expansion-roadmap.md](./docs/planning/full-expansion-roadmap.md):

| Period | Release | Outcome |
|--------|---------|---------|
| 2026 Q2 | v0.0.0 | Planning — **now** |
| 2026 Q2–Q3 | v0.1.0 | Play in Cursor via stdio MCP |
| 2026 Q3 | v0.2.0 | Play in ChatGPT via tunnel |
| 2026 Q3–Q4 | v0.3–v0.4 | Templates, export, hardening |
| 2027+ | v1.x MVP | Web UI (map, timeline, lore editor) |

---

## Non-goals (prototype)

Do not scope into v0.x without a new issue:

- Multi-user accounts / cloud sync
- Web map UI
- Full rules engine / dice
- World Anvil import
- ECS / OAuth deploy (tunnel only for ChatGPT)
- Licensed IP modules (user premise only; no shipped Pokémon assets)

---

## Maintainer gates

- **Never merge PRs** unless explicitly asked.
- **Never cut releases/tags** unless explicitly asked.
- **Never implement from planning docs alone** — need an open sub-issue.

---

## Key docs (read order)

| Order | Doc | Why |
|-------|-----|-----|
| 1 | [HANDOFF.md](./HANDOFF.md) | This file |
| 2 | [AGENTS.md](./AGENTS.md) | Agent rules |
| 3 | [mcp-tools-design.md](./docs/planning/mcp-tools-design.md) | What to build in v0.1 |
| 4 | [scenario-bootstrap.md](./docs/planning/scenario-bootstrap.md) | Premise / `start_campaign` behavior |
| 5 | [full-expansion-roadmap.md](./docs/planning/full-expansion-roadmap.md) | Long-term fan-out (don't build yet) |

---

## Open questions (for maintainer)

Not blocking v0.1 — decide when convenient:

1. **Repo location:** prototype lives under `prototyper/prototypes/worldkeep`; standalone clone at `~/projects/worldkeep` is fine too.
2. **Go module path:** e.g. `github.com/Bmsandoval/worldkeep` vs nested path — pick on #4.
3. **Single vs multi campaign in one process:** v0.1 design assumes one active campaign; explicit switch in v0.3.
4. **When to close #3 / tag v0.0.0:** after maintainer reviews planning commit.
