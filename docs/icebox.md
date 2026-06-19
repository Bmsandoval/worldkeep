# Icebox — deferred product ideas

Ideas **explicitly out of active scope** until promoted back to [roadmap.md](./roadmap.md) and filed as GitHub issues.

---

## Campaign seats & human ↔ AI handoff (v1.4.0–v1.6.0)

**Status:** Icebox (2026-06-18) — product direction paused; design retained for reference.

**Design doc:** [participant-handoff.md](./participant-handoff.md)

**What was the idea:** Track who controls each campaign role (DM seat, player/companion seats) and allow mid-session swaps between human and AI controllers — friend joins as a companion, human DM takeover, release back to AI.

**Why iceboxed:** WorldKeep’s near-term focus is continuity (memory, retrieval, admin UI, rules reference). Multi-participant seat control adds auth, invites, floor management, and UI complexity before the core actor/party intelligence layer is proven.

**Experimental code on `develop`:** Schema v3/v4 (`campaign_seats`, `session_floor`) and MCP tools (`list_campaign_seats`, `handoff_seat`, `release_seat_to_ai`, etc.) remain in the repo for possible revival but are **not** the recommended next build. See [playtest-handoff.md](./playtest-handoff.md) if you need to exercise the prototype.

**GitHub (frozen):** Milestones v1.4.0–v1.6.0 ([#93](https://github.com/Bmsandoval/worldkeep/issues/93), [#97](https://github.com/Bmsandoval/worldkeep/issues/97), [#100](https://github.com/Bmsandoval/worldkeep/issues/100)); do not pick up without explicit re-promotion.

**Promotion criteria:** Stable Phase 3 actor MCP (`prepare_actor_context`, companion profiles); clear playtest demand for human guests at the table; owner/auth story for invites.

---

## Narrative optimization

See [narrative-optimization.md](./narrative-optimization.md) and [roadmap.md](./roadmap.md) § Icebox.
