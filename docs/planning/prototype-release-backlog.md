# Prototype release backlog

Index of **GitHub release epics** for Worldkeep. Parent issues hold sub-issue checklists; this doc mirrors structure for humans.

**Repo:** [Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep)

## v0.0.0 — Planning + repo bootstrap

**Theme:** Vision, full roadmap, scenario bootstrap design, AGENTS, issues.

**Parent:** [#3](https://github.com/Bmsandoval/worldkeep/issues/3)

| # | Sub-issue | Status |
|---|-----------|--------|
| [#1](https://github.com/Bmsandoval/worldkeep/issues/1) | Planning docs, AGENTS.md, and full expansion roadmap | done (this commit) |
| [#2](https://github.com/Bmsandoval/worldkeep/issues/2) | GitHub milestones, labels, and release issue structure | done |

**Tag:** `v0.0.0`

---

## v0.1.0 — Core MCP + SQLite

**Theme:** stdio MCP, schema, `start_campaign`, core tools, tests.

**Parent:** [#10](https://github.com/Bmsandoval/worldkeep/issues/10)

| # | Sub-issue |
|---|-----------|
| [#4](https://github.com/Bmsandoval/worldkeep/issues/4) | Go module, SQLite schema, and migrations |
| [#5](https://github.com/Bmsandoval/worldkeep/issues/5) | stdio MCP server with server instructions |
| [#6](https://github.com/Bmsandoval/worldkeep/issues/6) | Campaign lifecycle tools |
| [#7](https://github.com/Bmsandoval/worldkeep/issues/7) | Read tools |
| [#8](https://github.com/Bmsandoval/worldkeep/issues/8) | Write tools |
| [#9](https://github.com/Bmsandoval/worldkeep/issues/9) | Integration test: premise to search round-trip |

**Tag:** `v0.1.0`

---

## v0.2.0 — ChatGPT via tunnel

**Theme:** Streamable HTTP, tunnel script, ChatGPT smoke path.

**Parent:** [#14](https://github.com/Bmsandoval/worldkeep/issues/14)

| # | Sub-issue |
|---|-----------|
| [#11](https://github.com/Bmsandoval/worldkeep/issues/11) | Streamable HTTP transport |
| [#12](https://github.com/Bmsandoval/worldkeep/issues/12) | Tunnel script and ChatGPT setup docs |
| [#13](https://github.com/Bmsandoval/worldkeep/issues/13) | Tool annotations for ChatGPT |

**Tag:** `v0.2.0`

---

## v0.3.0 — Templates + export

**Theme:** Scenario template packs, map tool, export.

**Parent:** [#18](https://github.com/Bmsandoval/worldkeep/issues/18)

| # | Sub-issue |
|---|-----------|
| [#15](https://github.com/Bmsandoval/worldkeep/issues/15) | Scenario template packs |
| [#16](https://github.com/Bmsandoval/worldkeep/issues/16) | get_map and connect_locations |
| [#17](https://github.com/Bmsandoval/worldkeep/issues/17) | export_campaign and import_campaign JSON |

**Tag:** `v0.3.0`

---

## v0.4.0 — Hardening

**Theme:** Multi-session playtest fixes.

**Parent:** [#22](https://github.com/Bmsandoval/worldkeep/issues/22)

| # | Sub-issue |
|---|-----------|
| [#19](https://github.com/Bmsandoval/worldkeep/issues/19) | summarize_session and check_contradiction |
| [#20](https://github.com/Bmsandoval/worldkeep/issues/20) | Provenance source field on writes |
| [#21](https://github.com/Bmsandoval/worldkeep/issues/21) | Multi-session ChatGPT playtest notes doc |

**Tag:** `v0.4.0`

---

## MVP (future — v1.x)

Not filed until prototype `v0.4.0` criteria met. See [full-expansion-roadmap.md](./full-expansion-roadmap.md) §5 Web companion app.
