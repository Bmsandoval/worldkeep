# Session Lifecycle

## Purpose

Defines the expected flow of gameplay inside WorldKeeper.

---

# Phase 1

## Session Preparation

DM starts session.

WorldKeeper:

* Loads campaign
* Loads active plots
* Loads recent events
* Loads continuity warnings

Produces:

Session Brief

---

# Phase 2

## Active Play

Player acts.

AI requests context.

WorldKeeper returns:

* Relevant actors
* Relevant events
* Relevant relationships
* Relevant plots

AI responds.

---

# Phase 3

## World Changes

Important events occur.

Examples:

```text
NPC recruited

Location destroyed

Relationship changed
```

WorldKeeper records events.

No canon updates yet.

---

# Phase 4

## Update Proposal

At major milestones:

AI generates:

Proposed Changes

Examples:

```text
Trust +10

Plot Advanced

New Fact
```

WorldKeeper stores proposals.

---

# Phase 5

## Review

DM reviews proposals.

Options:

```text
Approve

Reject

Modify
```

---

# Phase 6

## Commit

Approved updates become canon.

Facts updated.

Relationships updated.

Plots updated.

---

# Phase 7

## Session Close

WorldKeeper generates:

* Session Summary
* Timeline Entry
* Campaign Updates

Stored permanently.

---

# Core Principle

Events happen first.

Canon changes second.

This prevents accidental corruption of campaign history.

