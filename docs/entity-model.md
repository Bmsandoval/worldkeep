# Entity Model

## Purpose

This document defines the canonical data model used throughout WorldKeeper.

All systems should operate on these entities rather than introducing duplicate concepts.

---

# Core Principle

WorldKeeper is built around:

```text
Entity
Relationship
Fact
Event
```

Everything else derives from these concepts.

---

# Campaign

Top-level container.

Represents a single game world and campaign.

Contains:

* Sessions
* Actors
* Locations
* Plots
* Events
* Facts
* Rules
* Relationships

---

# Session

Represents a play session.

Contains:

* Session Summary
* Events
* Proposed Updates
* Timeline Entries

Purpose:

Track how the world changed during play.

---

# Actor

Any entity capable of action.

Subtypes:

```text
Player Character
Companion
NPC
Faction
Creature
```

Shared Properties:

* Name
* Description
* Goals
* Beliefs
* Memories
* Relationships
* Status

---

# Location

A place.

Examples:

* City
* Tavern
* Dungeon
* Kingdom
* Region

Contains:

* Description
* Current State
* Current Events
* Residents

---

# Plot

A narrative thread.

States:

```text
Active
Dormant
Resolved
Failed
```

Contains:

* Summary
* Participants
* Related Events
* Related Actors

---

# Event

A historical occurrence.

Examples:

```text
Warehouse burned

King assassinated

Companion recruited
```

Contains:

* Timestamp
* Participants
* Consequences
* Session

Events are immutable.

---

# Fact

A statement about the world.

Examples:

```text
Finn lost his left eye.

The Duke rules Blackport.
```

Contains:

* Confidence
* Visibility
* Source

Facts may be superseded but never deleted.

---

# Relationship

Connects two entities.

Examples:

```text
Trust
Fear
Friendship
Loyalty
Hatred
```

Contains:

* Source Entity
* Target Entity
* Relationship Type
* Value

---

# Secret

Information not universally known.

Visibility:

```text
DM Only
Known To Party
Known To Actor
Public
```

---

# Rule

Campaign-specific mechanics.

Examples:

```text
Flanking grants +3.

Leadership feat banned.
```

Stored separately from world facts.

---

# Memory

Actor-specific remembered event.

Examples:

```text
Player saved Lia.

Marcus betrayed party.
```

Different actors may remember the same event differently.

---

# Belief

Actor interpretation of reality.

Example:

Truth:

Finn works for Guild.

Belief:

Lia suspects Finn.

Beliefs do not need to be true.

---

# Goal

Long-term objective.

Examples:

```text
Find brother.

Overthrow king.

Protect party.

Goals influence decisions.

---

# Ruleset (Future)

Optional future entity.

Contains:

* Attributes
* Resources
* Abilities
* Progression

Not required for core roadmap.

