# Product phases

How **prototype**, **MVP**, and **platform** relate. GitHub **milestones** track work:

- **`v0.0.x`** — planning and repo bootstrap
- **`v0.x.0` minors** — thin prototype slices
- **`v1.x` MVP** (later) — shippable product gate

## Phase overview

| Phase | Version line | What "done" means |
|-------|--------------|-------------------|
| **Prototype** | `v0.x.0` | Prove **scenario bootstrap + MCP memory loop** locally; ChatGPT via tunnel |
| **MVP** | `v1.x.0` (TBD) | Shippable campaign studio — web UI, stable connector story, export, ops |
| **Platform** | Post-MVP | Cloud sync, collaboration, marketplace, integrations |

**Now:** **Prototype** only.

## Prototype releases (planned)

| Release | Theme | Outcome |
|---------|--------|---------|
| **v0.0.0** | Planning + repo | Vision, roadmap, issues, AGENTS — **current** |
| **v0.1.0** | Core MCP + SQLite | stdio MCP, schema, `start_campaign`, core read/write tools, tests |
| **v0.2.0** | ChatGPT + tunnel | Streamable HTTP, tunnel docs/script, MCP Inspector + ChatGPT smoke path |
| **v0.3.0** | Templates + polish | Scenario template packs, `get_map`, FTS tuning, campaign export JSON |
| **v0.4.0** | Hardening | Conflict hints, session summary tool, real multi-session playtest fixes |

Each release = one **parent issue** + sub-issues; squash-merge to `develop`.

## Prototype constraints (agreed)

| Area | Choice |
|------|--------|
| **Runtime** | Local machine only |
| **ChatGPT** | Tunnel to localhost MCP (cloudflared or ngrok) |
| **Auth** | None |
| **Campaigns** | SQLite files under `./data/` |
| **Bootstrap** | Required `start_campaign` with natural-language **premise** |

## MVP (future — not scoped in v0.x issues)

Likely includes (exact list via issues later):

- Web app — campaign list, location browser, event timeline, manual edits
- Stable remote MCP hosting (optional self-host)
- Campaign import/export UX
- Scenario template gallery (user-authored)
- Production backup and privacy story
- ChatGPT connector without manual tunnel (if platform allows)

## Platform (north star)

See [full-expansion-roadmap.md](./full-expansion-roadmap.md) — collaboration, mechanics packs, embeddings, integrations. Issue-driven only.

## GitHub visibility

| Surface | Convention |
|---------|------------|
| **Repo About** | `Prototype · MCP world memory for LLM-assisted RPG and fiction play` |
| **Topics** | `prototype`, `mcp`, `golang`, `sqlite`, `rpg`, `interactive-fiction` |
| **Milestone** | Description prefix: `Prototype:` + slice name |
| **Parent issue** | `Release v0.x.0 — Prototype: <theme>` |
| **Label `prototype`** | On v0.x work |

Do not label prototype work as `MVP` until **`v1.x`** is explicitly planned.
