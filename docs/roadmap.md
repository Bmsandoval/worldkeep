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

Minimal browser admin for dashboard, canon approval, world browse, and (later) seat management.

### Delivers

* REST API foundation (v1.1.0)
* Dashboard + approval queue in browser (v1.2.0) — **first web UI**
* World browser + session timeline (v1.3.0)
* Seat invite/handoff UI (v1.6.0, after participant handoff MCP)

### Rough sequence (after v1.0.0)

```text
v1.1.0  REST API        (~1 release)
v1.2.0  Admin UI        (~1 release)  ← first browser milestone
v1.3.0  Browse + session UI
v1.4.0  Campaign seats  (MCP; see participant-handoff.md)
v1.5.0  Handoff human↔AI
v1.6.0  Seat UI + invites
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

### Related: participant handoff

Human or AI may **control** each seat (DM or party member) and swap mid-campaign — see [participant-handoff.md](./participant-handoff.md) (v1.4.0–v1.6.0).

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

Interesting ideas that may never justify implementation.

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
Web UI foundation + admin (Phase 2.5)
↓
Campaign seats + handoff (v1.4–v1.5)
↓
Party Intelligence (actors)
↓
Campaign Intelligence
```

Phase 2.5 UI and v1.4–v1.5 seats can overlap with Phase 3 actor MCP work; seat **handoff** (v1.5) should follow actor profiles.

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

