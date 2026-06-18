# WorldKeeper Mature Product Design Doc

## 1. Product Vision

WorldKeeper becomes the canonical memory, simulation, continuity, and narrative intelligence layer for tabletop RPGs and AI-driven storytelling.

Its purpose is no longer merely storing campaign information.

Its purpose is maintaining a living world that persists independently of any individual DM, AI model, or game session.

The world exists whether players interact with it or not.

## 2. Long-Term Product Thesis

Current campaign tools are passive.

Examples:

* Obsidian
* Notion
* World Anvil
* Google Docs
* OneNote

They store information.

WorldKeeper becomes active.

It understands:

* what happened
* what is happening
* what should happen next

without taking control away from the DM.

## 3. Product Pillars

### Pillar 1: Canon

Maintain world truth.

### Pillar 2: Continuity

Prevent contradictions.

### Pillar 3: Simulation

Advance the world between sessions.

### Pillar 4: Narrative Intelligence

Identify meaningful consequences.

### Pillar 5: AI Orchestration

Provide high-quality context to any AI model.

## 4. Evolution Beyond MVP

### MVP

World remembers.

### Mature Product

World evolves.

The system should be capable of answering:

> What changed in the kingdom while the players spent three months in a dungeon?

without requiring manual DM preparation.

## 5. World State Engine

Introduce a dedicated simulation layer.

### Purpose

Track the current condition of the world.

Example dimensions:

```text
Prosperity
Stability
Food
Military Strength
Religious Influence
Crime
Trade
Population
Magic Saturation
```

Locations become dynamic.

Example:

Blackport

Session 1

```text
Prosperity: 70
Crime: 40
Stability: 80
```

Session 60

```text
Prosperity: 35
Crime: 85
Stability: 20
```

Because player actions altered history.

## 6. Faction Simulation

Factions become autonomous actors.

Each faction possesses:

```text
Goals
Resources
Relationships
Territory
Knowledge
Influence
```

Example:

Crimson Guild

Goals:

* Expand smuggling
* Corrupt city officials

Resources:

* Gold
* Informants
* Safe houses

The faction can generate actions independently.

Example:

```text
Guild bribes harbor master
Guild assassinates witness
Guild acquires new warehouse
```

Actions become proposed world events.

DM approves.

## 7. World Timelines

Introduce a true timeline system.

Every world change receives:

```text
Timestamp
Session
Actors
Consequences
```

WorldKeeper can reconstruct any point in history.

Questions become possible:

```text
What did Finn know six months ago?
```

```text
Who controlled Blackport before the coup?
```

```text
When did the prince disappear?
```

## 8. Knowledge Engine

One of the most valuable future systems.

Separate:

### Truth

What actually happened.

### Belief

What an entity thinks happened.

### Rumor

What is circulating publicly.

### Secret

What remains hidden.

Example:

Truth:

```text
Duke murdered predecessor.
```

Belief:

```text
Captain believes predecessor died naturally.
```

Rumor:

```text
People suspect poison.
```

Secret:

```text
Only Duke knows full details.
```

This dramatically improves roleplay realism.

## 9. Relationship Engine

Every entity becomes graph-connected.

Relationships exist between:

* NPCs
* factions
* locations
* players
* organizations

Examples:

```text
Trust
Fear
Love
Loyalty
Debt
Hatred
Respect
```

Relationships evolve automatically from events.

Example:

```text
Party saves Finn

Trust +15
Loyalty +10
Fear -5
```

The graph becomes a major narrative resource.

## 10. Narrative Intelligence Layer

The most ambitious future component.

Purpose:

Identify unresolved narrative opportunities.

Example:

The system notices:

```text
Prince missing
Guild expanding
Queen losing support
```

WorldKeeper suggests:

```text
Potential Story Arc:
Civil war brewing.
```

Not as generated content.

As analysis.

The DM remains author.

## 11. Consequence Engine

One of the strongest differentiators.

Player actions create downstream effects.

Example:

Players burn warehouse.

Immediate consequence:

```text
Guild loses supplies.
```

Later consequences:

```text
Food prices rise.
Guild retaliates.
Dock workers unemployed.
```

WorldKeeper proposes outcomes.

DM approves.

## 12. AI Context Optimization

By mature stage:

Campaigns may contain:

```text
10,000+ facts
1,000+ NPCs
500+ locations
Years of history
```

No AI can consume all of this.

WorldKeeper becomes an intelligent context compiler.

Input:

```text
Party visits Finn.
```

Output:

Only the 20-50 facts most relevant to the scene.

The AI never sees irrelevant campaign history.

## 13. AI Memory Profiles

Different AI models receive different context packages.

Examples:

### DM Profile

Receives:

* secrets
* future plans
* hidden plots

### Player Profile

Receives:

* discovered information
* public knowledge

### NPC Profile

Receives:

* personal beliefs
* relationships
* goals

This enables highly specialized AI interactions.

## 14. Campaign Copilot

Introduce a dedicated AI assistant.

Functions:

### Preparation

Creates session briefs.

### Recall

Answers world questions.

### Consistency

Flags contradictions.

### Analytics

Highlights neglected plots.

### Summaries

Produces journals and recaps.

This becomes the DM's second brain.

## 15. Multi-Agent World Simulation

Potential future direction.

Separate agents represent:

* factions
* kingdoms
* religions
* major NPCs

Each agent receives limited knowledge.

Agents generate actions.

WorldKeeper adjudicates outcomes.

Result:

The world feels alive between sessions.

## 16. World Analytics

Dashboard expands dramatically.

Examples:

### Campaign Health

```text
Active Plots
Resolved Plots
Player Agency
Narrative Density
```

### World Health

```text
Wars
Famines
Crises
Political Stability
```

### DM Health

```text
Unresolved Threads
Abandoned NPCs
Forgotten Locations
```

## 17. Import and Learning

The mature platform should ingest:

* PDFs
* campaign notes
* transcripts
* Discord logs
* Obsidian vaults
* VTT exports

WorldKeeper automatically builds:

* entities
* timelines
* plots
* relationships

from existing content.

## 18. Cross-Campaign Universes

Campaigns become linked.

Example:

Campaign A:

```text
Kingdom falls.
```

Campaign B:

Occurs 300 years later.

WorldKeeper preserves continuity.

Shared universes become first-class citizens.

## 19. Collaboration Features

Support:

### Multiple DMs

Shared canon.

### Co-DMs

Different permissions.

### Players

Limited world visibility.

### Spectators

Read-only access.

## 20. Open Ecosystem

Expose robust APIs and MCP tools.

Third parties build:

* VTT integrations
* Discord bots
* campaign generators
* AI companions
* world simulators

WorldKeeper becomes infrastructure.

## 21. Mature MCP Surface

High-Level Operations

```ts
prepare_scene_context()
prepare_session_brief()
advance_world_time()
simulate_faction_turns()
generate_consequence_report()
generate_continuity_report()
explain_world_state()
analyze_narrative_threads()
```

These become orchestration tools.

Not CRUD endpoints.

## 22. Long-Term Architecture

```text
Frontend

API Layer

World Engine
Narrative Engine
Relationship Engine
Knowledge Engine
Simulation Engine

Search Layer
Embedding Layer

Postgres

Event Store

MCP Layer

AI Providers
```

## 23. Revenue Model

Potential tiers:

### Free

Single campaign.

### Pro

Unlimited campaigns.

### WorldKeeper AI

Narrative analysis.

### Team

Multi-DM support.

### Creator

Published worlds.

## 24. The End State

A DM should eventually be able to ask:

"Remind me why the Crimson Guild hates the party."

and receive:

```text
Session 12:
Party exposed smuggling route.

Session 18:
Party burned warehouse.

Session 31:
Guild leader's brother killed during raid.

Current relationship:
Hostile (-82)

Likely future action:
Retaliation attempt within 30 days.
```

without manually maintaining any notes.

## 25. Ultimate Product Statement

WorldKeeper is not a campaign manager.

It is a persistent world intelligence system.

Campaigns happen inside the world.

The world remembers what happened.

The world understands why it happened.

The world continues moving when nobody is looking.

