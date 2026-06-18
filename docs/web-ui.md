# WorldKeep Web UI — Design & Delivery Plan

## 1. Why a UI now

MVP proved the **continuity engine** via MCP. A web UI makes WorldKeep usable without Cursor/ChatGPT tool fluency and gives DMs a place to **review canon**, **approve updates**, and **see campaign health** at a glance.

The MCP server remains the AI integration path. The UI talks to a **REST API** that shares the same store — not a second source of truth.

## 2. Non-goals (first UI releases)

* Not a VTT, map editor, or combat tracker
* Not full party chat UI (comes with [participant-handoff.md](./participant-handoff.md) + Phase 3)
* Not multi-tenant SaaS on day one — local/single-campaign hosted prototype first

## 3. Architecture target

```text
Browser (React or Laravel Blade + HTMX)
        |
   REST API  ←── same Go service or thin API layer
        |
   SQLite / Postgres
        |
   MCP Server  ←── Cursor / ChatGPT unchanged
```

## 4. UI surfaces (from [mvp.md](./mvp.md) §16)

| Surface | Purpose | Milestone |
| ------- | ------- | --------- |
| **Campaign dashboard** | Active plots, open session, pending approvals, continuity warnings | v1.2.0 |
| **Approval queue** | Review `propose_world_update` bundles; commit/reject | v1.2.0 |
| **World browser** | NPCs, locations, factions, plots, events (read + link to entity) | v1.3.0 |
| **Session view** | Timeline: events, summary, entities touched, pending from session | v1.3.0 |
| **Campaign seats** (later) | Who controls DM / each party slot (human vs AI) | v1.4.0+ with [participant-handoff.md](./participant-handoff.md) |

## 5. Delivery epics & rough timeline

Rough order **after v1.0.0 MVP** (no calendar commitments — sequence and effort bands only):

| Order | Milestone | Epic | Effort | Outcome |
| ----- | --------- | ---- | ------ | ------- |
| 1 | **v1.1.0** | UI foundation | ~1 release | REST API for dashboard, entities, pending updates, sessions; OpenAPI; local auth stub |
| 2 | **v1.2.0** | Minimal admin UI | ~1 release | **First browser UI** — dashboard + approval queue wired to API |
| 3 | **v1.3.0** | World & session UI | ~1 release | World browser + session timeline |
| 4 | **v1.4.0** | Campaign seats (MCP + schema) | ~1 release | Seat model; can run headless before seat UI |
| 5 | **v1.5.0** | Seat handoff | ~1 release | Human ↔ AI swap for DM and party members |
| 6 | **v1.6.0** | Seat management UI | ~0.5 release | Invite/join/handoff screens in web app |

**When you get a web UI:** end of **v1.2.0** (dashboard + approval queue in a browser).

**When seats are joinable in UI:** **v1.6.0**, after MCP handoff logic in v1.5.0.

Parallel work: Phase 3 **party actor** MCP (personalities, `prepare_actor_context`) can proceed alongside v1.1–v1.3; seat handoff (v1.4–v1.5) should follow actor foundations from [party-system.md](./party-system.md).

## 6. Tech choices (prototype defaults)

| Layer | Default | Notes |
| ----- | ------- | ----- |
| API | Go HTTP routes alongside `worldkeep-mcp-http` or shared `internal/api` | Reuse `internal/store` |
| Frontend | React (Vite) or Laravel kit from prototyper factory | Match maintainer preference at v1.2 kickoff |
| Auth | Local single-user → campaign invite tokens | Full OAuth deferred |
| Deploy | Same Fargate pattern as infra/ when hosted | SQLite local dev |

## 7. Success criteria

* DM can **approve a pending update** in the browser without MCP
* Dashboard matches `get_campaign_dashboard` data
* AI clients still work via MCP with no regression
* Path exists to seat UI (v1.6) without rewriting store

## 8. Planning refs

* [mvp.md](./mvp.md) §16 MVP UI
* [roadmap.md](./roadmap.md) — Phase 2.5 Web UI
* [participant-handoff.md](./participant-handoff.md) — human/AI seat cycling
* GitHub milestones **v1.1.0–v1.6.0** (see [HANDOFF.md](../HANDOFF.md))
