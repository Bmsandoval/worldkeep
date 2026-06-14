# Prototype release backlog

Index of **GitHub release epics** for Worldkeep. Parent issues hold sub-issue checklists; this doc mirrors structure for humans.

**Repo:** [Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep)

## v0.0.0 — Planning + repo bootstrap

**Theme:** Vision, full roadmap, scenario bootstrap design, AGENTS, issues.

| # | Sub-issue | Status |
|---|-----------|--------|
| TBD | Planning docs and AGENTS.md | open |
| TBD | GitHub milestones, labels, release structure | open |

**Tag:** `v0.0.0`

---

## v0.1.0 — Core MCP + SQLite

**Theme:** stdio MCP, schema, `start_campaign`, core tools, tests.

| Sub-issue | Scope |
|-----------|--------|
| Go module + SQLite migrations | Schema from [mcp-tools-design.md](./mcp-tools-design.md) |
| stdio MCP skeleton + server instructions | initialize, tools/list |
| Campaign tools | `start_campaign`, `load_campaign`, `list_campaigns` |
| Read tools | `get_scene_context`, `get_location`, `get_entity`, `search_world`, `get_player_state` |
| Write tools | `move_to`, `upsert_location`, `upsert_entity`, `record_event`, `add_lore`, `update_player_state` |
| Integration test | Premise → move → entity → search round-trip |

**Tag:** `v0.1.0`

---

## v0.2.0 — ChatGPT via tunnel

**Theme:** Streamable HTTP, tunnel script, ChatGPT smoke path.

| Sub-issue | Scope |
|-----------|--------|
| HTTP transport | `POST /mcp`, `/healthz` |
| Tunnel script + docs | cloudflared; ngrok notes |
| Tool annotations | ChatGPT hints |
| Smoke test guide | MCP Inspector + ChatGPT setup checklist |

**Tag:** `v0.2.0`

---

## v0.3.0 — Templates + export

**Theme:** Scenario template packs, map tool, export.

| Sub-issue | Scope |
|-----------|--------|
| Template packs | `blank`, `journey`, `mystery`, `salvage` seeds |
| `get_map` + `connect_locations` | Discovered graph |
| `export_campaign` / `import_campaign` | JSON backup |
| FTS tuning | Tag boosts, premise in search index |

**Tag:** `v0.3.0`

---

## v0.4.0 — Hardening

**Theme:** Multi-session playtest fixes.

| Sub-issue | Scope |
|-----------|--------|
| `summarize_session` | Session digest lore |
| `check_contradiction` | Warning-only conflict hints |
| Provenance on writes | `source` field enforcement |
| Playtest doc | Real ChatGPT campaign notes |

**Tag:** `v0.4.0`

---

## MVP (future — v1.x)

Not filed until prototype `v0.4.0` criteria met. See [full-expansion-roadmap.md](./full-expansion-roadmap.md) §5 Web companion app.

Update issue numbers in this file after running `scripts/create_github_issues.py`.
