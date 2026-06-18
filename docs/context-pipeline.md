# Context Pipeline

## Purpose

Defines how WorldKeeper selects information for AI consumption.

This document represents the most important system in the platform.

---

# Problem

Campaigns eventually become too large.

AI cannot consume:

* Every NPC
* Every Event
* Every Fact

for every response.

Context must be curated.

---

# User Input

Example:

```text
Talk to Finn.
```

---

# Context Compiler

WorldKeeper receives request.

Identifies:

* Actor
* Location
* Topic
* Session State

---

# Retrieval

WorldKeeper gathers:

## Actor Context

Finn

* Facts
* Relationships
* Beliefs
* Memories

---

## Plot Context

Relevant active plots.

---

## Event Context

Recent related events.

---

## Relationship Context

Connections to player and companions.

---

## Continuity Context

Warnings.

Contradictions.

Special notes.

---

# Ranking

Items scored by:

* Relevance
* Recency
* Importance
* Relationship Strength

---

# Output

Context Package

Example:

```text
Actor:
Finn

Recent Event:
Warehouse Fire

Relationship:
Distrusts Party

Plot:
Missing Prince

Warning:
Finn missing left eye
```

---

# AI Response

AI generates response using context package.

Not full campaign history.

---

# Goal

Provide maximum relevance with minimum tokens.

