# roadmap.md

# WorldKeeper Roadmap

## Vision

WorldKeeper is a persistent world intelligence system for AI-assisted campaigns.

The goal is to provide:

* world memory
* character memory
* continuity management
* campaign intelligence

for long-running tabletop and narrative experiences.

The roadmap prioritizes features that directly improve campaign continuity and AI reliability before pursuing simulation or personalization systems.

---

# Active Roadmap

These phases represent the intended development order.

---

## Phase 1 — POC

### "Can GPT remember?"

Document:

```text
poc.md
```

### Goal

Prove that campaign information can be stored outside of the conversation and reliably retrieved by an AI.

### Delivers

* Campaign storage
* NPC storage
* Location storage
* Event storage
* Plot storage
* Fact storage
* Rule/ruling storage
* Context retrieval
* Canon update proposals

### Success Metric

AI maintains continuity across multiple sessions and chats.

---

## Phase 2 — MVP

### "Can GPT run a campaign?"

Document:

```text
mvp.md
```

### Goal

Transform WorldKeeper into the authoritative campaign source of truth.

### Delivers

* Session management
* Session summaries
* Campaign dashboard
* Plot tracking
* Relationship tracking
* Secrets management
* Timeline management
* Canon validation
* Campaign imports
* Search

### Success Metric

A DM can successfully run a long-term campaign using WorldKeeper.

**Status:** MCP backend complete (v0.7.0–v1.0.0). Browser UI deferred to Phase 2.5 — see [web-ui.md](./web-ui.md).

---

## Phase 2.5 — Web UI

### "Can a DM run this without MCP?"

Document:

```text
web-ui.md
```

### Goal

Minimal browser admin for dashboard, canon approval, world browse, and session timeline. **Seat management UI is icebox** — see [icebox.md](./icebox.md).

### Delivers

* REST API foundation (v1.1.0) ✅
* Dashboard + approval queue in browser (v1.2.0) ✅
* World browser + session timeline (v1.3.0) ✅
* ~~Seat invite/handoff UI (v1.6.0)~~ → **icebox** ([participant-handoff.md](./participant-handoff.md))

### Rough sequence (after v1.0.0)

```text
v1.1.0  REST API        ✅
v1.2.0  Admin UI        ✅
v1.3.0  Browse + session UI ✅
v1.7.0  Open5e SRD tools (next)
---
icebox: v1.4.0 seats, v1.5.0 handoff, v1.6.0 seat UI
```

### Success Metric

DM approves canon and preps sessions in the browser; MCP clients unchanged.

---

## Phase 3 — Party Intelligence

### "Can characters remain consistent?"

Document:

```text
party-system.md
```

### Goal

Create persistent companions and AI-controlled party members.

### Delivers

* Actor system
* Companion entities
* Personality profiles
* Goals
* Emotional state
* Relationship graphs
* Knowledge separation
* Memory tracking
* Party discussions
* Party reactions

### Success Metric

Companions remain recognizable and consistent after dozens of sessions.

### Related (icebox): participant handoff

Human ↔ AI seat swapping (v1.4.0–v1.6.0) is **deferred** — see [icebox.md](./icebox.md) and [participant-handoff.md](./participant-handoff.md). Phase 3 actor work does not depend on seat handoff.

---

## Phase 4 — Campaign Intelligence

### "Can WorldKeeper help the DM?"

Document:

```text
world-intel.md
```

### Goal

Reduce DM workload through analysis and continuity assistance.

### Delivers

* Forgotten thread detection
* Continuity warnings
* Narrative debt tracking
* Session replay
* Consequence forecasting
* Campaign health metrics
* Session ingestion
* Historical queries
* Context compilation

### Success Metric

DM preparation time is significantly reduced.

---

# Backlog

These are likely future phases, but should not block the core roadmap.

---

## Owlbear Rodeo — tactical map integration

Document: [owlbear-integration.md](./owlbear-integration.md)  
Tracking: GitHub **[#107](https://github.com/Bmsandoval/worldkeep/issues/107)**

Lightweight browser VTT via **WorldKeep-hosted Owlbear extension** + WebSocket command bridge so the LLM moves tokens (players do not manually apply grid directions). Preferred over Foundry-as-a-service due to license/ops cost. Foundry remains optional BYOL for power users.

Promotion criteria: after REST API (#82) and optional first UI (#87); when playtests need a real battle board without Foundry economics.

---

## Ruleset Engine

Document:

```text
ruleset-engine.md
```

### Why Backlog

Very valuable.

Not required to prove the core vision.

WorldKeeper can be extremely successful while supporting a small number of systems.

### Goal

Allow arbitrary RPG systems to be created and managed through WorldKeeper.

### Delivers

* Custom attributes
* Custom resources
* Ability systems
* Progression systems
* Advancement trees
* Archetypes
* Rules validation
* Balance evaluation
* Ruleset generation

### Promotion Criteria

Move to Active Roadmap when users begin requesting custom systems beyond supported rulesets.

---

## Living World

Document:

```text
future.md
```

### Why Backlog

High complexity.

High engineering cost.

Many features depend on actor systems and campaign intelligence already existing.

### Goal

Create worlds that evolve independently of player actions.

### Delivers

* Faction simulation
* Political simulation
* Economic simulation
* Autonomous actors
* Consequence propagation
* World progression
* Legacy systems
* Shared universes

### Promotion Criteria

Move to Active Roadmap after campaign intelligence is stable and widely used.

---

# Icebox

Interesting ideas that may never justify implementation — or are **paused** until core continuity + actors are proven.

---

## Campaign seats & participant handoff

Document:

```text
participant-handoff.md
icebox.md
```

### Why Icebox (2026-06-18)

Multi-participant seat control (human joins as companion, DM takeover, release to AI) adds auth, invites, floor rules, and UI before party intelligence is stable. Prototype MCP/schema may remain on `develop` for reference; **do not extend** without explicit re-promotion.

### Would deliver (if revived)

* Campaign seats schema + MCP (v1.4.0 — prototype exists)
* `handoff_seat` / session floor (v1.5.0 — prototype exists)
* Browser invite/join/handoff UI (v1.6.0)

### Promotion criteria

Phase 3 actor MCP stable; playtest demand for human guests; invite/auth story agreed.

---

## Narrative Optimization

Document:

```text
narrative-optimization.md
```

### Why Icebox

This solves a luxury problem.

Campaign continuity and actor consistency are much higher value.

Most of the benefits can likely be approximated using campaign intelligence.

### Features

* Player preference modeling
* Engagement analysis
* Narrative pacing analysis
* Favorite NPC detection
* Story structure analysis
* Personalized recommendations

### Revisit If

Users explicitly request personalized campaign analytics.

Or

Player-specific optimization becomes a major differentiator.

---

# Strategic Priorities

## Highest Priority

```text
POC
↓
MVP (MCP)
↓
Web UI foundation + admin (Phase 2.5) ✅ through v1.3.0
↓
Open5e rules reference (v1.7) + Party Intelligence (actors)
↓
Campaign Intelligence
```

Phase 3 actor MCP and v1.7 Open5e are the recommended next tracks. **Campaign seats + handoff (v1.4–v1.6) are icebox** — see [icebox.md](./icebox.md).

These phases represent the core WorldKeeper vision.

Everything beyond this is optional.

---

## Secondary Priority

```text
Ruleset Engine
```

Potentially very valuable.

Should only be tackled after the core product proves itself.

---

## Long-Term Experiments

```text
Living World

Narrative Optimization
```

These are potentially transformative but should not distract from validating the core product.

---

# Product Thesis

WorldKeeper succeeds if it can reliably answer:

"What happened?"

"Who knows that?"

"Why does this character care?"

"What should I remember?"

before attempting to answer:

"What kind of story should we tell next?"

or

"What happens if the world simulates itself?"

