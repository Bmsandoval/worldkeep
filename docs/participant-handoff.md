# Campaign Seats & Participant Handoff

> **Status: Icebox (2026-06-18)** — Human ↔ AI seat swapping is **not** active product direction. This document is retained as design reference. See [icebox.md](./icebox.md). Experimental MCP/schema code may remain on `develop` but should not be extended without re-promotion.

## 1. Problem

Today WorldKeep assumes **one local operator** driving MCP (solo DM+player with AI narration). Real campaigns need:

* **Solo start** — you + GPT as DM + one or more GPT party members
* **Friend joins** — a human takes over an existing companion seat
* **Human DM takeover** — a real DM replaces AI DM mid-campaign
* **Participant leaves** — seat reverts to AI without losing character continuity

WorldKeep must track **who controls each role**, not just what the world state is.

## 2. Concept: Campaign seats

A **seat** is a control slot, not necessarily a person forever.

| Seat type | Typical controller | Maps to |
| --------- | ------------------ | ------- |
| `dm` | Human or AI | Session authority, secrets scope, canon approval |
| `player` | Human or AI | One party actor (PC or companion) |

Each seat has:

```json
{
  "id": "seat_dm",
  "campaign_id": "campaign_001",
  "seat_type": "dm",
  "controller": "ai",
  "controller_user_id": null,
  "actor_id": null,
  "display_name": "AI Dungeon Master",
  "status": "active"
}
```

Player seat example:

```json
{
  "id": "seat_player_2",
  "seat_type": "player",
  "controller": "human",
  "controller_user_id": "user_friend_01",
  "actor_id": "companion_lia",
  "display_name": "Lia (Alex)",
  "status": "active"
}
```

### Controller values

| Value | Meaning |
| ----- | ------- |
| `human` | A connected user narrates/ decides for this seat |
| `ai` | MCP client uses actor/DM profile + WorldKeep context |

### Status values

| Value | Meaning |
| ----- | ------- |
| `active` | Seat participates in session |
| `vacant` | No controller; assign before play |
| `paused` | Temporarily AI-substituted (human away) |

## 3. User stories

### Solo campaign

> As a **player**, I start alone with AI DM and AI companions, so I can play without scheduling a group.

* One `dm` seat → `controller: ai`
* N `player` seats → `controller: ai`, each linked to a companion actor

### Friend joins

> As a **host**, my friend takes over Lia, so they play their character while WorldKeep preserves Lia's history.

* Host calls `handoff_seat(seat_id, controller: human, user_id: …)`
* Lia's actor profile, memories, and relationships unchanged
* Scene context for Lia uses `scope` appropriate to what Lia knows

### Human DM takeover

> As a **new DM**, I replace AI DM, so I run canon and secrets while the party keeps playing.

* `handoff_seat` on `dm` seat: `ai` → `human`
* Human DM gets `scope: dm` on dashboard and secrets
* AI DM instructions stop applying to that seat

### Player leaves

> As a **host**, when a player leaves, their character returns to AI control without breaking continuity.

* `handoff_seat(seat_id, controller: ai)` — optional `pause` first
* `prepare_actor_context(actor_id)` resumes AI voice using stored personality + memory

## 4. Session floor rules (orchestration)

WorldKeep stores **who may speak**; the client (UI or MCP host) enforces turn flow.

| Mode | Behavior |
| ---- | -------- |
| **Player-led** (default) | One player/companion seat has floor; DM responds when invoked |
| **Party beat** | Structured queue of seat lines; player checkpoint between beats |
| **Open table** | All human seats may interject; AI seats only when called |

Floor state (stored per open session):

```json
{
  "floor_seat_id": "seat_player_1",
  "party_beat_queue": [],
  "awaiting_player_checkpoint": false
}
```

See [party-system.md](./party-system.md) for companion dialogue design; handoff extends who sits in each seat.

## 5. MCP surface (planned)

### Read

* `list_campaign_seats(campaign_id)`
* `get_seat(seat_id)` — controller, actor, display name
* `get_session_floor(session_id)` — who may narrate now

### Write

* `create_player_seat(actor_id, display_name?)` — links companion/PC
* `assign_seat_controller(seat_id, controller, user_id?)` — admin/host
* `handoff_seat(seat_id, controller, user_id?, reason?)` — audit logged
* `release_seat_to_ai(seat_id, reason?)` — shorthand for player/DM leave

### Context

* `prepare_actor_context(actor_id, seat_id?)` — personality + knowledge for current controller
* Existing tools respect seat: human DM must approve commits; AI DM proposes only

## 6. Permissions interaction

Builds on MVP `set_campaign_role` / `WORLDKEEP_ROLE`:

| Role | Seats |
| ---- | ----- |
| **owner** | Create seats, handoff any seat, invite users |
| **dm** (human seat) | Handoff player seats, not owner-only campaign delete |
| **player** | Control assigned player seat only; no dm scope |

## 7. Delivery milestones (icebox — not scheduled)

| Milestone | Would deliver | Notes |
| --------- | ------------- | ----- |
| **v1.4.0** | Seats schema, `list_campaign_seats`, `assign_seat_controller`, demo seats on Blackport | Prototype merged; **frozen** |
| **v1.5.0** | `handoff_seat`, floor state, `prepare_actor_context` integration, handoff playtest script | Prototype merged; **frozen** |
| **v1.6.0** | Web UI: invite link, pick seat, release to AI | Never started; **icebox** |

**Dependency (if revived):** companion/actor entities from Phase 3 MCP should land before seat handoff is productized; handoff without actor profiles is seat metadata only.

## 8. Demo scenario (acceptance)

1. Seed Blackport with AI DM + AI companions Finn-proxy + Lia
2. Solo session via MCP — AI DM narrates
3. Handoff Lia seat to human — human speaks as Lia; facts still party-scoped correctly
4. Handoff DM to human — human approves commit; secrets visible with dm scope
5. Release Lia to AI — AI Lia answers in-character using prior session facts

## 9. Out of scope (initial handoff)

* Voice/video presence
* Cross-campaign seat reuse
* Automatic matchmaking for empty seats
* Replacing a **dead** PC with a new actor (separate resurrection/plot workflow)
