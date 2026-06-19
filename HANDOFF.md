# WorldKeep — Handoff

_Last updated: 2026-06-18_

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

**Active phase: Post-MVP — Web UI + participant handoff** — see [docs/roadmap.md](./docs/roadmap.md)

POC ✅ v0.6.0 · MVP MCP ✅ v1.0.0 · Playtest ✅ [docs/playtest-notes.md](./docs/playtest-notes.md)

**Next recommended:** [#94](https://github.com/Bmsandoval/worldkeep/issues/94) participant handoff (v1.5.0), or Phase 3 actor MCP in parallel.

---

## GitHub issue queue (Post-MVP — active)

| Milestone | Parent | Sub-issues | When (rough) |
| --------- | ------ | ---------- | ------------ |
| **v1.1.0** REST API | [#84](https://github.com/Bmsandoval/worldkeep/issues/84) | #82–#83 | ~1 release after v1.0.0 |
| **v1.2.0** First browser UI | [#87](https://github.com/Bmsandoval/worldkeep/issues/87) | #85–#86 | ~1 release — **dashboard + approvals in browser** |
| **v1.3.0** Browse + session UI | [#90](https://github.com/Bmsandoval/worldkeep/issues/90) | #88–#89 | ~1 release |
| **v1.4.0** Campaign seats | [#93](https://github.com/Bmsandoval/worldkeep/issues/93) | #91–#92 | Seats schema + MCP |
| **v1.5.0** Human ↔ AI handoff | [#97](https://github.com/Bmsandoval/worldkeep/issues/97) | #94–#96 | Friend joins / DM takeover / leave → AI |
| **v1.6.0** Seat UI + invites | [#100](https://github.com/Bmsandoval/worldkeep/issues/100) | #98–#99 | Browser join/handoff |
| **v1.7.0** Open5e + optional DDB | [#101](https://github.com/Bmsandoval/worldkeep/issues/101) | #102–#106 | **Open5e SRD tools (primary)**; optional DDB party/game-log |

Design: [docs/web-ui.md](./docs/web-ui.md) · [docs/participant-handoff.md](./docs/participant-handoff.md) · [docs/open5e-integration.md](./docs/open5e-integration.md) · [docs/dndbeyond-integration.md](./docs/dndbeyond-integration.md) · [docs/owlbear-integration.md](./docs/owlbear-integration.md)

**Backlog (tactical maps):** [#107](https://github.com/Bmsandoval/worldkeep/issues/107) Owlbear integration

---

## GitHub issue queue (MVP — complete)

| Milestone | Parent | Status |
| --------- | ------ | ------ |
| **v0.7.0** Campaign dashboard | [#67](https://github.com/Bmsandoval/worldkeep/issues/67) | ✅ #64–#66 |
| **v0.8.0** Secrets + visibility | [#71](https://github.com/Bmsandoval/worldkeep/issues/71) | ✅ #68–#70 |
| **v0.9.0** Session workspace | [#75](https://github.com/Bmsandoval/worldkeep/issues/75) | ✅ #72–#74 |
| **v1.0.0** MVP completion | [#79](https://github.com/Bmsandoval/worldkeep/issues/79) | ✅ #76–#78 |

---

## GitHub issue queue (POC — complete)

| Milestone | Parent | Status |
| --------- | ------ | ------ |
| **v0.1.0** Manual world store | [#25](https://github.com/Bmsandoval/worldkeep/issues/25) | ✅ #23–#24 |
| **v0.2.0** MCP read (stdio) | [#30](https://github.com/Bmsandoval/worldkeep/issues/30) | ✅ #26–#29 |
| **v0.3.0** Propose/commit | [#34](https://github.com/Bmsandoval/worldkeep/issues/34) | ✅ #31–#33 |
| **v0.4.0** Session workflow | [#38](https://github.com/Bmsandoval/worldkeep/issues/38) | ✅ #35–#37 |
| **v0.5.0** Conflict + playtest | [#41](https://github.com/Bmsandoval/worldkeep/issues/41) | ✅ #39–#40 |
| **v0.6.0** ChatGPT tunnel | [#45](https://github.com/Bmsandoval/worldkeep/issues/45) | ✅ #42–#44 |

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
    serve.sh                 ← local Go + Laravel (two ports)
    create_github_issues.py
    realign_github_issues.py
    realign_mvp_github_issues.py
    realign_post_mvp_github_issues.py
    playtest-mcp.sh
    playtest-stdio.sh
    tunnel.sh
  web/                       ← Laravel UI (dashboard + approvals)
  Dockerfile                 ← Apache + Go on one container (:80)
  mcp/
    cmd/worldkeep-serve/     ← unified MCP + REST (preferred)
    cmd/worldkeep-mcp/       ← stdio (Cursor)
    cmd/worldkeep-mcp-http/  ← alias → unified server
    cmd/worldkeep-api/       ← alias → unified server
    internal/store/          ← SQLite campaign store
    internal/mcp/            ← MCP protocol + tools
    internal/api/            ← REST handlers (shares store)
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
| Campaign seats (v1.4.0) | ✅ `list_campaign_seats`, `assign_seat_controller` — `make backfill-seats` on existing DBs |
| MCP HTTP + tunnel (ChatGPT) | ✅ `make serve` or Docker — `/mcp` proxied on same host as UI |
| REST API (v1.1.0) | ✅ unified in `worldkeep-serve` — [docs/rest-api.md](./docs/rest-api.md) |
| Laravel web UI (v1.2.0 WIP) | ✅ dashboard + approvals under `web/` — `make serve` |
| World browser + sessions (v1.3.0) | ✅ entity browse/search + session timeline UI |
| Single-service deploy | ✅ `Dockerfile` — Apache :80 + Go loopback :8788 |
| MVP MCP tools (v0.7.0–v1.0.0) | ✅ 23 tools — dashboard, secrets, session workspace, hybrid search, import, roles |
| Live multi-chat playtest | ✅ [docs/playtest-notes.md](./docs/playtest-notes.md) |

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
2. Run `make test-all && make seed` — local stack: `make serve` ([docs/deploy.md](./docs/deploy.md)).
3. Branch from `develop`; implement only agreed scope.
4. `make test-all` before PR (Go + Laravel).
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
