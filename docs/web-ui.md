# WorldKeep Web UI — Design & Delivery Plan

## 1. Why a UI now

MVP proved the **continuity engine** via MCP. A web UI makes WorldKeep usable without Cursor/ChatGPT tool fluency and gives DMs a place to **review canon**, **approve updates**, and **see campaign health** at a glance.

The MCP server remains the AI integration path. The UI talks to a **REST API** that shares the same store — not a second source of truth.

## 2. Non-goals (first UI releases)

* Not a VTT, map editor, or combat tracker
* Not full party chat UI or multi-participant seat control (**icebox** — [icebox.md](./icebox.md))
* Not multi-tenant SaaS on day one — local/single-campaign hosted prototype first

## 3. Architecture target

```text
One service (local Docker / Fargate task):
  Apache :80 — Laravel Blade UI (/app/*)
    + /mcp, /api, /healthz served in-process by the same Laravel app
  WorldKeep engine — MCP + REST + SQLite, in-process (App\Services\WorldKeep)
  Cursor / ChatGPT → /mcp (same host as UI when deployed)
```

Laravel never duplicates canon logic — the engine runs in-process (`App\Services\WorldKeep\Engine`), called server-side.

## 4. UI surfaces (from [mvp.md](./mvp.md) §16)

| Surface | Purpose | Milestone |
| ------- | ------- | --------- |
| **Campaign dashboard** | Active plots, open session, pending approvals, continuity warnings | v1.2.0 |
| **Approval queue** | Review `propose_world_update` bundles; commit/reject | v1.2.0 |
| **World browser** | NPCs, locations, factions, plots, events (read + link to entity) | v1.3.0 |
| **Session view** | Timeline: events, summary, entities touched, pending from session | v1.3.0 ✅ |
| ~~**Campaign seats**~~ | ~~Who controls DM / each party slot~~ | **Icebox** — [icebox.md](./icebox.md) |

## 5. Delivery epics & rough timeline

Rough order **after v1.0.0 MVP** (no calendar commitments — sequence and effort bands only):

| Order | Milestone | Epic | Effort | Outcome |
| ----- | --------- | ---- | ------ | ------- |
| 1 | **v1.1.0** | UI foundation | ~1 release | REST API for dashboard, entities, pending updates, sessions; OpenAPI; local auth stub |
| 2 | **v1.2.0** | Minimal admin UI | ~1 release | **First browser UI** — dashboard + approval queue wired to API |
| 3 | **v1.3.0** | World & session UI | ~1 release | World browser + session timeline ✅ |
| — | **icebox** | Campaign seats + handoff + seat UI | — | v1.4.0–v1.6.0 deferred — [icebox.md](./icebox.md) |
| 4 | **v1.7.0** | Open5e SRD tools | ~1 release | Rules lookup MCP (primary next track) |

**When you get a web UI:** **v1.2.0** ✅ (dashboard + approval queue in a browser).

**Seat join/handoff UI:** **not scheduled** — icebox until re-promoted.

Parallel work: Phase 3 **party actor** MCP (personalities, `prepare_actor_context`) alongside v1.7 Open5e.

## 6. Tech choices (prototype defaults)

| Layer | Default | Notes |
| ----- | ------- | ----- |
| Engine | In-process PHP engine (`App\Services\WorldKeep`) — MCP + REST | Shares the Laravel SQLite store |
| Frontend | Laravel kit in `web/` | Dashboard + approval queue (v1.2) |
| Auth | Local session (Laravel) + optional `WORLDKEEP_API_TOKEN` for REST | Full OAuth deferred |
| Deploy | **Single Dockerfile** — Apache + PHP, engine in-process | No second Fargate service for MCP |

## 7. Success criteria

* DM can **approve a pending update** in the browser without MCP
* Dashboard matches `get_campaign_dashboard` data
* AI clients still work via MCP with no regression

## 8. Planning refs

* [mvp.md](./mvp.md) §16 MVP UI
* [roadmap.md](./roadmap.md) — Phase 2.5 Web UI
* [icebox.md](./icebox.md) — deferred seat handoff (v1.4–v1.6)
* GitHub milestones **v1.1.0–v1.3.0** ✅ · **v1.7.0** next (see [HANDOFF.md](../HANDOFF.md))
