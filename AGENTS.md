# Agent instructions — Worldkeep

**Repo:** `Bmsandoval/worldkeep` · **Integration branch:** `develop`

**Session handoff:** [HANDOFF.md](./HANDOFF.md) — read first on every new session.

---

## Development workflow (required)

Follow **[docs/planning/prototype-workflow.md](./docs/planning/prototype-workflow.md)** — shared planning-first, issue-driven process.

Templates: [issue-pr-workflow.md](./docs/planning/issue-pr-workflow.md), [prototype-release-backlog.md](./docs/planning/prototype-release-backlog.md).

```bash
gh issue list --repo Bmsandoval/worldkeep --state open
```

**Maintainer gates:** never merge PRs or cut releases unless explicitly asked. Never implement from planning docs without an open sub-issue.

---

## Product vision (one paragraph)

**Worldkeep** is external **world memory** for LLM-assisted play and fiction. A local MCP server stores locations, entities, events, lore, and player state in SQLite. The model **reads scoped context** before narrating and **writes durable facts** when the user moves, meets NPCs, or establishes canon. Users **bootstrap** each campaign with a natural-language premise (e.g. *"we are Pokémon trainers"*). ChatGPT connects via **local MCP + HTTPS tunnel** during the prototype; cloud hosting is later.

**North star (later):** Campaign manager with map/timeline UI, scenario template packs, GM/player visibility, optional mechanics layers, and cloud sync — see [full-expansion-roadmap.md](./docs/planning/full-expansion-roadmap.md).

**Prototype (build now, `v0.x`):** Prove the **memory loop** — bootstrap scenario → play with tools → resume without drift. **MVP** (`v1.x`, later) is a shippable product (accounts, UI, polish). Do not call prototype work "MVP" in issues.

---

## Stack (agreed for prototype)

| Area | Choice |
|------|--------|
| **Language** | **Go** — MCP server, SQLite, tests |
| **Storage** | **SQLite + FTS5** — one file per campaign under `./data/` |
| **MCP (local)** | **stdio** for Cursor / Claude Desktop |
| **MCP (ChatGPT)** | **Streamable HTTP** on localhost + **tunnel** (cloudflared/ngrok) — no cloud deploy in v0.2 |
| **Auth** | **None** in prototype — single user, local machine |

Pattern: follow [timelord/mcp](https://github.com/Bmsandoval/timelord/tree/feat/chatgpt-mcp/mcp) for remote HTTP shape; storage like ideator's SQLite ledger.

---

## Hard rules for the play agent (MCP server instructions)

When Worldkeep is connected, the client LLM must:

1. Call **`get_scene_context`** (or equivalent) before narrating during active play.
2. Call **write tools** when location, entities, or permanent facts change.
3. **Not contradict** stored canon without an explicit update tool call.
4. Treat tool output as **raw data** — Worldkeep stores; the LLM narrates.

Scenario bootstrap is **mandatory** for a new campaign via **`start_campaign`** (see [scenario-bootstrap.md](./docs/planning/scenario-bootstrap.md)).

---

## Non-goals (prototype `v0.x`)

- Multi-user cloud accounts
- Full TTRPG rules engine / dice automation (defer to schema packs later)
- Map visualization UI
- Import from World Anvil / Notion
- Production OAuth / ECS deploy (tunnel only for ChatGPT)

---

## Planning documents

All planning artifacts live under **`docs/planning/`**. Key docs:

| Doc | Role |
|-----|------|
| [product-vision.md](./docs/planning/product-vision.md) | Boundaries and primary offering |
| [product-phases.md](./docs/planning/product-phases.md) | Prototype vs MVP vs platform |
| [full-expansion-roadmap.md](./docs/planning/full-expansion-roadmap.md) | Every expansion direction + timeline |
| [scenario-bootstrap.md](./docs/planning/scenario-bootstrap.md) | Premise dictation and templates |
| [mcp-tools-design.md](./docs/planning/mcp-tools-design.md) | Tools and data model |
| [local-mcp-architecture.md](./docs/planning/local-mcp-architecture.md) | Local run + tunnel |

Planning informs issues; **issues drive implementation**.
