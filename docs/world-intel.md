# WorldKeeper MVP+2 Design Doc

# World Intelligence & Continuity Engine

## 1. Purpose

MVP proved:

* world memory
* continuity
* campaign state

MVP+1 proved:

* companion persistence
* actor memory
* party dynamics

MVP+2 focuses on a new problem:

The campaign now contains too much information for either the DM or AI to manage manually.

WorldKeeper should begin helping the DM identify important information, consequences, contradictions, and opportunities.

The product evolves from:

```text
Memory System
```

into

```text
Campaign Intelligence System
```

## 2. Product Goal

The DM should feel like they have an intelligent campaign assistant.

The assistant should:

* surface forgotten content
* identify contradictions
* explain consequences
* track narrative state
* maintain continuity

without taking narrative control away from the DM.

## 3. Core Philosophy

WorldKeeper does not create stories.

WorldKeeper helps maintain stories.

It should recommend.

Never dictate.

## 4. New Capability Categories

### Continuity Intelligence

### Narrative Intelligence

### Campaign Analytics

### Context Optimization

### Session Ingestion

## 5. Forgotten Thread Engine

### Problem

Players and DMs forget old hooks.

Example:

```text
Session 8:
Missing prince

Session 26:
Still unresolved
```

Nobody remembers.

### Solution

WorldKeeper continuously scans for:

* unresolved quests
* missing NPCs
* abandoned locations
* broken promises
* dormant mysteries

### MCP

```ts
get_forgotten_threads()

get_dormant_npcs()

get_abandoned_plots()
```

### Example Output

```text
Forgotten Threads

Missing Prince
Last referenced: 17 sessions ago

Ashfall Mine
Last visited: 22 sessions ago

Finn's Debt
Last discussed: 11 sessions ago
```

## 6. Continuity Warning Engine

### Problem

Humans and AI contradict established facts.

### Solution

Before committing updates:

WorldKeeper validates:

* facts
* entities
* timelines
* relationships

### MCP

```ts
generate_continuity_warnings()

check_for_conflicts()
```

### Example

```text
Warning:

Marcus claims he witnessed the assassination.

Timeline indicates Marcus was not present.
```

## 7. Session Replay System

### Purpose

Explain why the world looks the way it does.

### Example Queries

```text
Why does Finn hate the party?

Why did the Guild become hostile?

When did Lia start distrusting Marcus?
```

### MCP

```ts
explain_relationship()

explain_world_state()

explain_plot_history()
```

### Example Output

```text
Finn distrusts the party because:

Session 12
Smuggling route exposed

Session 18
Warehouse burned

Session 31
Brother arrested
```

## 8. Narrative Debt Engine

### Problem

Campaigns accumulate unresolved mysteries.

Eventually players stop caring.

### Metrics

Track:

```text
Active mysteries

Resolved mysteries

Outstanding promises

Abandoned hooks

Average age of unresolved plots
```

### MCP

```ts
analyze_narrative_debt()
```

### Example

```text
Narrative Debt Score

High

14 unresolved mysteries

Only 2 resolved in last 10 sessions
```

## 9. Consequence Forecasting

### Problem

DMs struggle to predict long-term effects.

### Solution

WorldKeeper suggests likely outcomes.

### Example Event

```text
Party burns warehouse.
```

### Suggested Consequences

```text
Immediate

Guild loses resources

Short-Term

Dockworkers unemployed

Long-Term

Crime rises
```

### MCP

```ts
forecast_consequences(
    event_id
)
```

### Output

Suggestions only.

Never automatic.

## 10. Campaign Health Dashboard

### Purpose

Provide campaign-level visibility.

### Metrics

```text
Active Plots

Dormant Plots

Outstanding Promises

Major Threats

Recently Introduced NPCs

Forgotten NPCs

Continuity Warnings
```

### New Dashboard

Campaign Health becomes a first-class page.

## 11. Spotlight Tracking

### Purpose

Detect imbalances.

Track:

```text
Player involvement

Companion involvement

NPC prominence

Faction activity
```

### Example

```text
Player A
52%

Player B
18%

Player C
30%
```

### MCP

```ts
analyze_spotlight_distribution()
```

## 12. Session Ingestion Engine

One of the most valuable features.

### Input Sources

* ChatGPT exports
* Discord logs
* Session transcripts
* Notes
* Markdown

### Processing

Extract:

* events
* facts
* NPC updates
* plot progression
* relationship changes

### MCP

```ts
ingest_session()

generate_update_proposal()
```

### Outcome

Dramatically reduces bookkeeping.

## 13. Canon Confidence System

### Problem

Not all facts are equally trustworthy.

### Fact Confidence Levels

```text
High

Medium

Low
```

### Example

```text
Duke has daughter
High

Duke fought dragon
Low
```

### Purpose

Prevent accidental canonization of hallucinations.

## 14. Temporal Query Engine

### Purpose

Query historical state.

### Examples

```text
Who ruled Blackport 20 sessions ago?

What did Lia know before Session 15?

Which plots were active when the Duke died?
```

### MCP

```ts
query_historical_state()
```

## 15. Context Compiler

### Problem

Campaigns become too large.

### Goal

Provide only relevant context.

### Input

```text
Party enters tavern.
```

### Output

```text
Relevant NPCs

Relevant plots

Relevant rumors

Relevant relationships
```

### MCP

```ts
compile_scene_context()
```

This becomes the primary context endpoint for AI.

## 16. World Timeline Explorer

New UI.

Visual timeline showing:

* events
* plot progression
* NPC deaths
* faction changes

Useful for both DMs and AI debugging.

## 17. Relationship Explorer

Graph view.

Questions become easy:

```text
Who trusts the party?

Who opposes the Guild?

Who knows about the prince?
```

## 18. Success Criteria

The phase succeeds if:

### DM Workload

Reduced substantially.

### Continuity

Contradictions become rare.

### Recall

Important campaign details are easy to find.

### Bookkeeping

Session ingestion handles most updates.

### AI Quality

Scene context becomes more accurate and token-efficient.

## 19. What This Unlocks

After MVP+2, WorldKeeper possesses:

* memory
* actors
* continuity intelligence
* narrative intelligence

At this point the platform is ready for:

* autonomous faction simulation
* world progression
* dynamic economies
* kingdom systems
* persistent worlds

which belong in the Mature Product phase.

## 20. Product Statement

MVP+2 transforms WorldKeeper from a campaign database into a campaign analyst.

The system no longer merely remembers what happened.

It helps the DM understand why it matters.

