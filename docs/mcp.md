# MCP Specification

## Purpose

Defines the MCP interface exposed by WorldKeeper.

GPT should interact with WorldKeeper exclusively through these operations.

---

# Context

## get_campaign_overview()

Returns:

* Campaign Summary
* Current Session
* Active Plots
* Major Actors

---

## search_world(query)

Searches all entities.

---

## get_entity(id)

Returns full entity.

---

## compile_scene_context(input)

Returns:

* Relevant Actors
* Relevant Events
* Relevant Facts
* Relevant Relationships
* Relevant Plots

Primary context endpoint.

---

# Sessions

## start_session()

Creates session.

---

## end_session()

Closes session.

---

## get_recent_events()

Returns recent campaign activity.

---

# Events

## record_event()

Creates event.

---

## explain_event()

Explains event history.

---

# Actors

## prepare_actor_context(actor_id)

Returns:

* Personality
* Goals
* Beliefs
* Relationships
* Memories

---

## get_actor_relationships()

---

## get_actor_memories()

---

## get_actor_beliefs()

---

# Continuity

## check_for_conflicts()

Detect contradictions.

---

## generate_continuity_warnings()

Returns warnings.

---

# Campaign Intelligence

## get_forgotten_threads()

---

## analyze_narrative_debt()

---

## explain_relationship()

---

## explain_world_state()

---

# Updates

## propose_world_update()

Creates pending update.

---

## commit_world_update()

Approves update.

---

## reject_world_update()

Rejects update.

---

# Future

Reserved:

```text
Ruleset Engine

Faction Simulation

Narrative Optimization
```

