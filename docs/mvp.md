# WorldKeeper MVP Design Doc

## 1. Vision

WorldKeeper is an AI-native campaign operating system that maintains campaign continuity, tracks world state, manages canon, and provides structured context to AI Dungeon Masters.

The MVP expands beyond memory storage and introduces:

* World state management
* Session workflows
* Canon validation
* Context generation
* AI-assisted campaign administration

The MVP should support campaigns that last months or years without significant continuity degradation.

## 2. Product Goals

The MVP must solve three major problems.

### Problem 1: Memory Loss

DMs forget details.

AI forgets details.

Campaign notes become unsearchable.

Solution:

Structured canon storage.

---

### Problem 2: Continuity Drift

NPCs change personalities.

Locations change descriptions.

Plot threads disappear.

House rulings get forgotten.

Solution:

Canonical entity management and continuity validation.

---

### Problem 3: Context Overload

By session 40:

* hundreds of NPCs
* dozens of locations
* years of history

Neither humans nor AI can easily determine what matters right now.

Solution:

AI-generated contextual briefings.

## 3. Target User

Primary User:

AI-assisted Dungeon Master

Examples:

* ChatGPT DM
* Claude DM
* Gemini DM
* Human DM using AI assistance

Secondary User:

Traditional DMs who want campaign continuity tools.

## 4. Core User Story

The DM opens WorldKeeper before a session.

WorldKeeper generates:

* active plots
* nearby NPCs
* unresolved consequences
* upcoming timers
* prior rulings

The AI receives a focused context package.

The session occurs.

The AI proposes world updates.

The DM approves changes.

WorldKeeper updates canon.

Next session begins with continuity intact.

## 5. MVP Principles

### Principle 1

Humans own canon.

AI suggests canon.

### Principle 2

Every fact has a source.

### Principle 3

Nothing is deleted.

Everything becomes historical.

### Principle 4

World state is derived from events.

Not vice versa.

## 6. New Capabilities Beyond POC

### Campaign Dashboard

Provides:

* current session
* active plots
* recent events
* continuity warnings
* unresolved approvals

Purpose:

Single place for campaign health.

---

### Session Workspace

Each session becomes a first-class object.

Contains:

* session notes
* AI summaries
* events created
* entities modified
* pending updates

Example:

Session 37

* Finn recruited
* Prince discovered alive
* Crimson Guild warehouse burned

---

### Canon Review Queue

Instead of immediate writes:

AI proposes:

```text
Proposed Changes

NPC Finn
+ Trust toward party increased

Faction Crimson Guild
- Power reduced from 65 to 55

New Event
Warehouse fire at docks
```

DM reviews.

DM approves.

Canon changes.

## 7. Expanded Data Model

### Relationships

New entity type.

```json
{
  "source": "npc_finn",
  "target": "party",
  "type": "trust",
  "value": 35
}
```

Purpose:

Track evolving relationships.

---

### Knowledge Objects

Store beliefs separately from facts.

```json
{
  "owner": "npc_finn",
  "text": "The Duke is secretly funding smugglers."
}
```

True or false does not matter.

Belief matters.

---

### Secrets

```json
{
  "title": "Prince still alive",
  "visibility": "dm_only"
}
```

Used to prevent accidental AI spoilers.

---

### Timers

```json
{
  "title": "Demon Ritual",
  "remaining_days": 14
}
```

World advances automatically.

## 8. Context Engine

This becomes the most important system.

### New MCP Function

```ts
prepare_scene_context()
```

Input:

```json
{
  "location": "Blackport",
  "participants": [
    "npc_finn",
    "party"
  ]
}
```

Output:

* relevant NPCs
* recent events
* active plots
* location summary
* continuity concerns
* known secrets
* nearby factions

This dramatically reduces token usage.

### Campaign Briefing

```ts
prepare_session_brief()
```

Returns:

* active plots
* due timers
* major NPC updates
* unresolved player choices
* rulings likely to matter

## 9. Search Improvements

POC uses SQL search.

MVP introduces:

### Hybrid Search

Keyword search

*

Embedding search

Queries:

```text
Find the bartender the party trusted.
```

Should locate Finn even if his name isn't mentioned.

## 10. Continuity Engine

New subsystem.

### Responsibilities

Detect:

* contradictory facts
* duplicate NPCs
* impossible timelines
* dead characters appearing alive
* location conflicts

Example:

```text
Warning:
Finn was recorded as dead in Session 28.
```

Before update approval.

## 11. Event Sourcing

Major architectural upgrade.

### POC

Stores current state.

### MVP

Stores:

* state
* event history

Every significant change creates an event.

Example:

```text
Event:
Party burns warehouse
```

Automatically affects:

* faction power
* plot progress
* location state

History remains recoverable.

## 12. AI Integration Layer

Support multiple models.

### Generic Context Endpoint

```ts
get_ai_context(
  prompt,
  scope
)
```

Returns optimized context package.

### AI Provider Agnostic

Support:

* ChatGPT
* Claude
* Gemini
* Local models

No provider-specific assumptions.

## 13. Permissions Model

Roles:

### Owner

Campaign creator.

### DM

Full access.

### Player

Limited access.

Cannot see:

* secrets
* hidden plots
* DM notes

This becomes important immediately when campaigns are shared.

## 14. Campaign Import

MVP should support importing existing campaigns.

### Sources

* Markdown
* Obsidian
* Notion export
* World Anvil export
* Plain text notes

AI assists with entity extraction.

Example:

Upload 300 pages of notes.

WorldKeeper creates:

* NPCs
* locations
* factions
* events
* plots

Automatically.

## 15. MVP MCP Surface

### Read

```ts
get_campaign_overview()
get_entity()
search_world()
get_active_plots()
get_recent_events()
prepare_scene_context()
prepare_session_brief()
get_relationships()
get_knowledge()
```

### Write

```ts
record_event()
propose_world_update()
commit_world_update()
create_secret()
create_timer()
advance_time()
record_ruling()
```

### Validation

```ts
check_for_conflicts()
generate_continuity_warnings()
```

## 16. MVP UI

### Dashboard

Cards:

* Active Plots
* Pending Approvals
* Timers
* Recent Events

### World Browser

Tabs:

* NPCs
* Locations
* Factions
* Plots
* Events

### Session View

Timeline of:

* events
* summaries
* approved updates

### Approval Queue

Review proposed changes.

Approve or reject.

## 17. Success Metrics

The MVP is successful if:

### Continuity

AI references prior campaign facts correctly.

### Recall

DM can locate any NPC, plot, or event within seconds.

### Adoption

Campaigns continue using WorldKeeper after 10+ sessions.

### Trust

DMs approve most AI-generated updates.

Target:

85%+ approval rate.

## 18. Future Features (Post-MVP)

Not included in MVP.

* Full faction simulation
* Economic simulation
* Weather systems
* Kingdom management
* Combat integration
* Character sheets
* Inventory tracking
* VTT integrations
* Discord bots
* Voice session support
* Automated campaign journals
* AI-generated recaps
* Multi-campaign universes

## 19. MVP Architecture

```text
Frontend
    |
WorldKeeper API
    |
Postgres
    |
Embedding Store
    |
MCP Server
    |
AI Clients
```

## 20. Real Product Thesis

The MVP is not competing with campaign wikis.

It is competing with human memory.

The user should eventually feel:

"I no longer need to remember every detail of my campaign. WorldKeeper remembers it for me, and my AI DM can actually use that memory correctly."

