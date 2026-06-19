# Agent instructions — WorldKeep

**Repo:** `Bmsandoval/worldkeep` · **Integration branch:** `develop`

**Session handoff:** [HANDOFF.md](./HANDOFF.md) — read first on every new session.

---

## Source of truth (read this)

| Priority | Source | Use it for |
| -------- | ------ | ---------- |
| **1 — Product** | **`docs/`** | What WorldKeep is, POC scope, data model, MCP surface, roadmap |
| **2 — Work queue** | **GitHub issues** (after realignment) | What to build right now |
| **3 — Process** | **`AGENTS.md`** + [docs/workflow/](./docs/workflow/) | How to branch, PR, test, release |

**Superseded:** The June 2026 bootstrap under `docs/planning/` (Pokémon premise, `start_campaign` / `move_to`, v0.1–v0.4 release backlog) is **obsolete**. Do not follow it. Git history preserves it for reference only.

**Canonical product docs:** [docs/README.md](./docs/README.md)

---

## Development workflow (required)

Follow **[docs/workflow/prototype-workflow.md](./docs/workflow/prototype-workflow.md)** — shared planning-first, issue-driven process.

Templates: [issue-pr-workflow.md](./docs/workflow/issue-pr-workflow.md).

```bash
gh issue list --repo Bmsandoval/worldkeep --state open
```

**Maintainer gates:** never merge PRs or cut releases unless explicitly asked. Never implement from docs alone without an open sub-issue agreed with the maintainer.

---

## Product vision (one paragraph)

**WorldKeep** is a **continuity engine** for AI-assisted tabletop RPGs and narrative play. It stores structured campaign memory (actors, locations, events, plots, facts, rulings) and exposes it via MCP so an AI client can **retrieve relevant context before narrating** and **propose canon updates** that a human approves. WorldKeep remembers; the AI reasons.

**Active phase:** **Post-MVP** — REST API, browser UI, participant handoff. See [docs/roadmap.md](./docs/roadmap.md) and [HANDOFF.md](./HANDOFF.md).

**Later phases:** MVP campaign OS → party intelligence → campaign intelligence. Backlog: ruleset engine, living world. Icebox: narrative optimization. See [docs/roadmap.md](./docs/roadmap.md).

---

## Stack (agreed for prototype)

| Area | Choice |
| ---- | ------ |
| **Runtime** | **Laravel (PHP 8.4)** — UI, MCP, REST API, storage |
| **Engine** | `App\Services\WorldKeep\` — Store, Engine, MCP handlers |
| **Storage** | **SQLite** locally; **Aurora PostgreSQL** in hub-prod (shared DB for Laravel + campaign tables) |
| **MCP (local)** | **stdio** — `make mcp` → `php artisan worldkeep:mcp` |
| **MCP (ChatGPT)** | HTTP `POST /mcp` on same host as UI |
| **Deploy** | **One PHP container** — Apache :80 ([docs/deploy.md](./docs/deploy.md)) |
| **Auth** | Cognito Hosted UI (prod); optional `WORLDKEEP_API_TOKEN` for REST |

Pattern: MCP HTTP shape follows [timelord/mcp](https://github.com/Bmsandoval/timelord/tree/feat/chatgpt-mcp/mcp).

---

## Hard rules for the play agent (MCP server instructions)

When WorldKeep is connected, the client LLM must:

1. Call **context retrieval** (`compile_scene_context` / `get_relevant_context`) before narrating during active play.
2. Use **propose → commit** update flow for canon changes — not silent overwrites.
3. **Not contradict** stored canon without an explicit update tool call.
4. Treat tool output as **raw data** — WorldKeep stores; the LLM narrates.

Session flow: [docs/session-lifecycle.md](./docs/session-lifecycle.md). Context pipeline: [docs/context-pipeline.md](./docs/context-pipeline.md).

---

## Non-goals (POC)

- VTT, map UI, combat tracker
- Full rules engine / dice automation
- Living world / faction simulation
- Narrative optimization / player modeling
- Multi-user cloud accounts
- Import from World Anvil / Notion

See [docs/poc.md](./docs/poc.md) §2 and [docs/roadmap.md](./docs/roadmap.md).

---

## Planning documents

All product artifacts live under **`docs/`**:

| Doc | Role |
| --- | ---- |
| [product-thesis.md](./docs/product-thesis.md) | Why WorldKeep exists |
| [roadmap.md](./docs/roadmap.md) | Phases, backlog, icebox |
| [entity-model.md](./docs/entity-model.md) | Canonical entities |
| [mcp.md](./docs/mcp.md) | MCP operations |
| [context-pipeline.md](./docs/context-pipeline.md) | Context retrieval (core product) |
| [session-lifecycle.md](./docs/session-lifecycle.md) | Play session flow |
| [poc.md](./docs/poc.md) | **What to build first** |
| [mvp.md](./docs/mvp.md) | Post-POC scope |
| [web-ui.md](./docs/web-ui.md) | Browser UI delivery |
| [rest-api.md](./docs/rest-api.md) | REST API |
| [deploy.md](./docs/deploy.md) | Unified Docker / hosting |

Planning informs issues; **issues drive implementation** once realigned to POC docs.
