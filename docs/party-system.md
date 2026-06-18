# WorldKeeper Expansion: Companion & Party Intelligence System

## 1. Objective

Expand WorldKeeper from a world continuity engine into a character continuity engine.

The goal is to enable AI-controlled party members that:

* maintain consistent personalities
* remember past events
* form opinions
* develop relationships
* disagree with each other
* react differently to the same situation
* evolve over time

The system should allow GPT to temporarily assume the role of a specific companion while preserving that companion's unique perspective.

## 2. Why This Exists

Current AI companions typically fail after long campaigns.

Common problems:

* personalities drift
* everyone sounds alike
* companions forget major events
* relationships reset
* emotional consequences disappear

The result is a party composed of generic AI voices.

WorldKeeper should make companions feel persistent.

## 3. Product Goal

The player should be able to ask:

> "Lia, what do you think?"

and receive an answer that:

* matches Lia's personality
* reflects Lia's history
* incorporates Lia's goals
* accounts for Lia's relationships
* respects what Lia actually knows

even after dozens of sessions.

## 4. New Concept: Actors

Introduce a generalized Actor model.

### Existing WorldKeeper

```text
NPC
Location
Faction
Plot
Event
```

### Expanded WorldKeeper

```text
Actor
├─ Player Character
├─ Companion
├─ NPC
├─ Organization
└─ Creature
```

All speaking entities become actors.

## 5. Companion Entity

A companion becomes a first-class entity.

Example:

```json
{
  "id": "companion_lia",
  "name": "Lia",
  "type": "companion",
  "class": "Ranger",
  "status": "alive"
}
```

However, most companion behavior comes from attached systems.

## 6. Personality System

### Purpose

Prevent voice drift.

### Traits

Store durable personality traits.

```json
{
  "cautious": 90,
  "optimistic": 20,
  "compassionate": 70,
  "aggressive": 15,
  "curious": 65
}
```

Traits influence responses.

### Archetype

```json
{
  "archetype": "Reluctant Survivor"
}
```

Used for quick context generation.

### Voice Notes

```json
{
  "speech_style": [
    "brief",
    "practical",
    "rarely jokes"
  ]
}
```

GPT receives these before speaking as Lia.

## 7. Goals System

Companions require personal motivations.

Example:

```json
{
  "goals": [
    {
      "text": "Find missing brother",
      "priority": 100
    },
    {
      "text": "Protect party",
      "priority": 80
    }
  ]
}
```

Goals influence decisions.

Two companions can interpret the same event differently because their goals differ.

## 8. Emotional State System

Separate permanent personality from temporary emotion.

Example:

```json
{
  "current_emotions": {
    "fear": 40,
    "anger": 10,
    "trust": 75,
    "hope": 60
  }
}
```

Emotions change frequently.

Personality changes rarely.

## 9. Relationship System

Relationships become first-class objects.

### Companion → Player

```json
{
  "trust": 80,
  "respect": 65,
  "friendship": 90
}
```

### Companion → Companion

```json
{
  "trust": 40,
  "respect": 85,
  "friendship": 20
}
```

### Companion → NPC

```json
{
  "trust": 15,
  "fear": 70
}
```

Relationships evolve through events.

## 10. Knowledge System

Most important feature.

### Truth

What actually happened.

### Knowledge

What the companion knows.

### Belief

What the companion thinks happened.

Example:

Truth:

```text
Finn works for the Crimson Guild.
```

Lia Knowledge:

```text
Finn met with suspicious people.
```

Lia Belief:

```text
Finn may be dangerous.
```

Marcus Knowledge:

```text
Finn has always been helpful.
```

Marcus Belief:

```text
Finn is trustworthy.
```

This creates genuine disagreement.

## 11. Memory System

Companions should not remember everything.

Store memories.

### Core Memories

Permanent.

```text
Player saved Lia from execution.
```

### Significant Memories

Long-term.

```text
Marcus betrayed the party.
```

### Recent Memories

Temporary.

```text
Party argued with mayor.
```

Memory importance determines retention.

## 12. Opinion System

Companions should form opinions.

Example:

```json
{
  "subject": "Finn",
  "opinion": "Suspicious",
  "confidence": 75
}
```

Opinions derive from:

* memories
* beliefs
* relationships
* goals

## 13. Party Dynamics Engine

New subsystem.

Responsible for:

* disagreements
* alliances
* rivalries
* friendships

Example:

Lia trusts Marcus.

Marcus distrusts Vex.

Vex likes the player.

The resulting party discussion becomes more interesting.

## 14. Party Conversation System

New MCP operation.

```ts
generate_party_discussion(
  topic,
  participants
)
```

Input:

```json
{
  "topic": "Should we trust Finn?"
}
```

Output:

Lia:
No.

Marcus:
Yes.

Vex:
Only if he pays us.

````

Generated using stored beliefs and relationships.

## 15. Reaction System

Companions should react automatically.

Example event:

```text
Player executes prisoner.
````

WorldKeeper generates:

```json
{
  "lia": "horrified",
  "marcus": "angry",
  "vex": "approving"
}
```

GPT uses these reactions during narration.

## 16. Actor Context Builder

Most important MCP feature.

### Function

```ts
prepare_actor_context(actor_id)
```

Returns:

* personality
* goals
* emotions
* memories
* relationships
* beliefs
* voice notes

GPT receives this before speaking as the actor.

## 17. New MCP Surface

### Read

```ts
get_actor(actor_id)

prepare_actor_context(actor_id)

get_actor_relationships(actor_id)

get_actor_goals(actor_id)

get_actor_memories(actor_id)

get_actor_beliefs(actor_id)

get_actor_emotional_state(actor_id)
```

### Write

```ts
record_actor_interaction(...)

update_actor_relationship(...)

add_actor_memory(...)

update_actor_belief(...)

update_actor_emotion(...)
```

### High-Level

```ts
generate_party_discussion(...)

generate_actor_reaction(...)

generate_party_reactions(...)
```

## 18. Session Workflow Changes

### Before

GPT retrieves:

* world context
* NPC context

### After

GPT retrieves:

* world context
* NPC context
* companion context

Before any companion dialogue.

## 19. UI Additions

### Party Screen

Displays:

* companions
* goals
* emotions
* relationships

### Relationship Graph

Shows:

* friendships
* rivalries
* trust levels

### Memory Timeline

Shows:

* significant memories
* relationship changes
* major decisions

## 20. Success Criteria

The feature succeeds if:

### Personality Persistence

Companions behave consistently after 50+ sessions.

### Knowledge Boundaries

Companions only use information they know.

### Relationship Continuity

Past events affect future interactions.

### Distinct Voices

Party members feel different from each other.

### Emergent Interaction

Companions occasionally disagree without explicit scripting.

## 21. Long-Term Evolution

This system eventually expands into a full Actor Engine.

Future actor types:

```text
Companions
NPCs
Villains
Kingdom Leaders
Factions
Gods
```

All become persistent entities with:

* goals
* memories
* beliefs
* relationships
* emotions

The same architecture that makes a companion feel alive can eventually make an entire world feel alive.

## 22. Product Impact

Before this expansion:

WorldKeeper remembers places.

After this expansion:

WorldKeeper remembers people.

The player should eventually feel that companions are not merely generated responses.

They are recurring characters with their own history, opinions, loyalties, and memories that persist across the life of the campaign.

