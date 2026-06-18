# WorldKeeper POC Design Doc

## 1. Goal

Build a minimal WorldKeeper app that proves a GPT-powered DM can maintain campaign continuity by reading and writing structured world state outside the chat session.

The POC should demonstrate that the GPT can:

1. Retrieve relevant world context before narrating.
2. Add new canon facts during play.
3. Track NPCs, locations, events, plots, and rulings.
4. Avoid contradicting previously stored information.
5. Summarize session changes into persistent world state.

## 2. Non-Goals

This POC will not attempt to build a full virtual tabletop, character sheet manager, combat engine, rules database, map tool, or complete campaign wiki.

It will not simulate economies, armies, religions, weather, calendars, or faction turns yet.

The POC is about proving memory reliability, not replacing the DM.

## 3. Core Hypothesis

A GPT DM becomes significantly more consistent if it can call a dedicated WorldKeeper service for campaign state instead of relying on chat memory alone.

Specifically, the POC should prove:

> Given a stored world state and session history, GPT can retrieve relevant facts, narrate consistently, propose updates, and preserve continuity across sessions.

## 4. User Experience

The DM starts a session by asking GPT to prepare the current campaign context.

Example:

> “Prepare for tonight’s session. The party is returning to Blackport.”

GPT calls WorldKeeper and receives:

* Current party location
* Relevant NPCs
* Active plots
* Recent events
* Known secrets
* Prior rulings
* Continuity warnings

During play, the DM can say:

> “The party convinces Finn to spy on the Crimson Guild.”

GPT proposes a world update:

* Finn’s relationship to the party increases.
* Finn gains a new belief: the party can be trusted.
* The Crimson Guild plot advances.
* A new event is recorded.

The DM can approve, reject, or modify the update.

## 5. POC Scope

### 5.1 Entities

The POC only needs these entity types:

* Campaign
* Session
* Location
* NPC
* Faction
* Plot
* Event
* Fact
* Ruling

Optional but useful:

* Secret
* Timer

## 6. Data Model

### Campaign

```json
{
  "id": "campaign_001",
  "name": "Shadows of Blackport",
  "system": "D&D 3.5e",
  "created_at": "2026-06-17T00:00:00Z"
}
```

### Location

```json
{
  "id": "location_blackport",
  "campaign_id": "campaign_001",
  "name": "Blackport",
  "type": "city",
  "description": "A fog-heavy port city ruled by Duke Harland.",
  "status": "tense",
  "parent_location_id": null,
  "tags": ["port", "political", "urban"]
}
```

### NPC

```json
{
  "id": "npc_finn",
  "campaign_id": "campaign_001",
  "name": "Finn",
  "location_id": "location_blackport",
  "status": "alive",
  "description": "One-eyed bartender at the Salt Lantern.",
  "attitude_to_party": 20,
  "goals": ["Keep his tavern safe", "Avoid angering the Crimson Guild"],
  "beliefs": [
    {
      "text": "The party may be useful allies.",
      "confidence": "medium",
      "source_event_id": "event_014"
    }
  ],
  "tags": ["bartender", "informant"]
}
```

### Faction

```json
{
  "id": "faction_crimson_guild",
  "campaign_id": "campaign_001",
  "name": "Crimson Guild",
  "description": "A smuggling syndicate operating through Blackport.",
  "power": 65,
  "attitude_to_party": -10,
  "goals": ["Control dockside trade", "Keep nobles dependent on smuggled goods"]
}
```

### Plot

```json
{
  "id": "plot_missing_prince",
  "campaign_id": "campaign_001",
  "title": "The Missing Prince",
  "status": "active",
  "urgency": "medium",
  "summary": "The prince disappeared after investigating Crimson Guild activity.",
  "involved_entities": ["faction_crimson_guild", "location_blackport"]
}
```

### Event

```json
{
  "id": "event_014",
  "campaign_id": "campaign_001",
  "session_id": "session_003",
  "title": "Party recruited Finn as an informant",
  "summary": "The party convinced Finn to spy on the Crimson Guild from the Salt Lantern.",
  "entities": ["npc_finn", "faction_crimson_guild"],
  "created_at": "2026-06-17T00:00:00Z"
}
```

### Fact

```json
{
  "id": "fact_033",
  "campaign_id": "campaign_001",
  "entity_id": "npc_finn",
  "text": "Finn lost his left eye in a dockside knife fight.",
  "visibility": "party_known",
  "confidence": "high",
  "source": {
    "type": "session",
    "session_id": "session_002"
  }
}
```

### Ruling

```json
{
  "id": "ruling_001",
  "campaign_id": "campaign_001",
  "question": "Does flanking grant +3 instead of +2?",
  "answer": "Yes. In this campaign, flanking grants +3.",
  "scope": "campaign",
  "system": "D&D 3.5e"
}
```

## 7. MCP Functions

### 7.1 Retrieval Functions

```ts
get_campaign_overview(campaign_id)
```

Returns campaign name, system, current session, current party location, active plots, and major open threads.

```ts
get_relevant_context(campaign_id, prompt, limit?)
```

The most important function in the POC.

Given a natural-language prompt, returns relevant entities, facts, events, plots, and rulings.

Example prompt:

> “The party returns to Blackport and asks Finn about the Crimson Guild.”

Expected return:

* Blackport location summary
* Finn NPC summary
* Crimson Guild faction summary
* Recent Finn-related events
* Relevant active plots
* Any continuity warnings

```ts
search_world(campaign_id, query, filters?)
```

Searches all world objects.

```ts
get_entity(entity_id)
```

Returns one full entity.

```ts
get_recent_events(campaign_id, limit)
```

Returns recent campaign events.

```ts
get_active_plots(campaign_id)
```

Returns unresolved plots.

```ts
search_rulings(campaign_id, query)
```

Searches prior rules rulings.

## 8. Write Functions

### 8.1 Safe Two-Step Update Flow

The POC should avoid direct uncontrolled writes from GPT.

Use:

```ts
propose_world_update(campaign_id, changes, reason)
```

Creates a pending update.

```ts
list_pending_updates(campaign_id)
```

Returns pending updates.

```ts
commit_world_update(update_id)
```

Applies the update.

```ts
reject_world_update(update_id, reason?)
```

Rejects the update.

This lets the GPT say:

> “I recommend saving the following changes.”

The DM remains the authority.

### 8.2 Direct Write Functions

For the POC, these can be used behind the commit flow.

```ts
create_entity(campaign_id, type, data)
update_entity(entity_id, patch, reason)
add_fact(campaign_id, entity_id, text, visibility, confidence, source)
record_event(campaign_id, session_id, title, summary, entities)
record_ruling(campaign_id, question, answer, scope)
```

## 9. Conflict Detection

The POC should include a simple conflict checker.

```ts
check_for_conflicts(campaign_id, proposed_changes)
```

Minimum viable version:

* Detect exact name duplicates.
* Detect status conflicts, such as alive vs dead.
* Detect location conflicts, such as an NPC being in two places at once.
* Detect direct fact contradictions when phrasing is obvious.
* Surface possible conflicts instead of blocking.

Example:

```json
{
  "warnings": [
    {
      "type": "possible_contradiction",
      "message": "Proposed update says Finn has both eyes, but existing fact says Finn lost his left eye."
    }
  ]
}
```

## 10. Session Flow

### Start Session

1. DM asks GPT to prepare.
2. GPT calls `get_campaign_overview`.
3. GPT calls `get_active_plots`.
4. GPT calls `get_recent_events`.
5. GPT presents session brief.

### During Session

1. DM narrates player actions.
2. GPT calls `get_relevant_context` when needed.
3. GPT narrates using returned context.
4. GPT proposes updates after meaningful changes.

### End Session

1. DM asks GPT to summarize.
2. GPT produces session summary.
3. GPT calls `propose_world_update`.
4. DM approves.
5. WorldKeeper records events, facts, relationship updates, and plot changes.

## 11. Minimal Database Tables

For a relational POC:

```sql
campaigns
sessions
entities
facts
events
rulings
pending_updates
```

Use a flexible JSON column for entity-specific data.

Example:

```sql
entities (
  id text primary key,
  campaign_id text not null,
  type text not null,
  name text not null,
  summary text,
  data jsonb not null,
  created_at timestamp not null,
  updated_at timestamp not null
)
```

```sql
facts (
  id text primary key,
  campaign_id text not null,
  entity_id text,
  text text not null,
  visibility text not null,
  confidence text not null,
  source_type text,
  source_id text,
  created_at timestamp not null
)
```

```sql
events (
  id text primary key,
  campaign_id text not null,
  session_id text,
  title text not null,
  summary text not null,
  entity_ids jsonb,
  created_at timestamp not null
)
```

```sql
pending_updates (
  id text primary key,
  campaign_id text not null,
  proposed_changes jsonb not null,
  reason text,
  status text not null,
  created_at timestamp not null
)
```

## 12. Success Criteria

The POC succeeds if GPT can:

1. Correctly recall stored facts across separate chats.
2. Use prior events to narrate consistent consequences.
3. Avoid leaking hidden secrets unless requested as DM-only context.
4. Detect obvious contradictions before committing updates.
5. Preserve prior rules rulings.
6. Produce useful session briefs from stored world state.
7. Produce useful end-of-session update proposals.

## 13. Demo Scenario

Seed the campaign with:

* Location: Blackport
* NPC: Finn, one-eyed bartender
* Faction: Crimson Guild
* Plot: Missing Prince
* Fact: Finn fears the Crimson Guild
* Ruling: Flanking is +3 in this campaign

Demo prompts:

1. “Prepare tonight’s session. The party is returning to Blackport.”
2. “The party visits Finn and asks about the Crimson Guild.”
3. “Finn agrees to spy for them.”
4. “End the session and save important changes.”
5. Start a new chat.
6. Ask: “Who is Finn and what does he know?”
7. Ask: “What is our flanking rule?”

If GPT answers consistently, the POC works.

## 14. Suggested Build Order

### Phase 1: Manual World Store

* Create database schema.
* Add CRUD API.
* Add simple search.
* Add seed data manually.

### Phase 2: MCP Read Access

* Implement `get_campaign_overview`.
* Implement `get_relevant_context`.
* Implement `search_world`.
* Implement `get_entity`.

### Phase 3: Proposed Updates

* Implement `propose_world_update`.
* Implement `commit_world_update`.
* Implement `reject_world_update`.

### Phase 4: Session Workflow

* Implement `record_event`.
* Implement `record_ruling`.
* Implement `get_recent_events`.
* Implement session summary saving.

### Phase 5: Conflict Warnings

* Add simple validation before commit.
* Surface warnings in pending updates.

## 15. POC Architecture

```text
ChatGPT / Custom GPT
        |
        | MCP calls
        v
WorldKeeper MCP Server
        |
        v
WorldKeeper API
        |
        v
Postgres / SQLite
```

For the fastest prototype, SQLite is enough.

For a hosted prototype, use Postgres.

## 16. Recommended Stack

Given the likely prototype goals:

* Backend: Go or Node
* Database: SQLite for local POC, Postgres for hosted
* MCP Server: TypeScript or Go
* Search: simple SQL LIKE first
* Later Search: embeddings or full-text search
* Frontend: minimal admin UI for reviewing pending updates

## 17. What to Defer

Do not build these yet:

* Full rules database
* Combat tracker
* VTT integration
* Map editor
* Inventory system
* Calendar simulation
* Economy simulation
* Relationship graph UI
* Embeddings
* Multi-user permissions
* Character builder

They are useful later, but not needed to prove the core idea.

## 18. Product Insight

The POC is not “a campaign wiki.”

It is a continuity engine.

The value is not merely storing lore. The value is giving an AI DM a reliable external memory with controlled canon updates.

The central loop is:

```text
Retrieve context → narrate consistently → propose changes → validate → commit canon
```

If that loop works, the product works.

