# WorldKeeper Expansion

# Player Intelligence & Narrative Optimization Engine

## 1. Purpose

WorldKeeper currently understands:

* the world
* the rules
* the actors
* the campaign

This expansion introduces a new capability:

```text
Understanding the player.
```

The goal is to help AI-driven campaigns adapt to the preferences, engagement patterns, and storytelling interests of individual players.

This system should improve campaign quality without removing player agency or DM control.

---

## 2. Product Vision

The player should eventually feel:

> "This campaign feels like it was written for me."

Not because the AI is cheating.

Because the system understands:

* what types of stories the player enjoys
* what types of challenges they engage with
* which NPCs matter to them
* which plots they pursue
* what causes them to disengage

---

## 3. Design Principles

### Observe, Don't Assume

Player preferences should emerge from behavior.

Not from stereotypes.

### Suggestions, Not Automation

WorldKeeper should advise.

The DM remains in control.

### Campaign-Specific First

Preferences should be learned within the context of a campaign.

### Privacy By Design

Player profiles belong to the player.

---

## 4. New System: Player Model

Introduce a first-class Player entity.

Current major entities:

```text
World
Actors
Rulesets
Plots
Events
```

Expanded:

```text
Player
World
Actors
Rulesets
Plots
Events
```

---

## 5. Player Profile

Example:

```json
{
  "player_id": "player_bryan",
  "campaign_id": "campaign_001"
}
```

The profile contains observations.

Not judgments.

---

## 6. Preference Tracking

Track observed engagement categories.

Examples:

```yaml
preferences:
  exploration: 85
  mystery: 90
  politics: 30
  combat: 45
  crafting: 20
  relationships: 95
```

Scores evolve over time.

Derived from behavior.

Not questionnaires.

---

## 7. Engagement Signals

WorldKeeper observes:

### Positive Signals

```text
Followed plot hook

Repeatedly engaged NPC

Asked follow-up questions

Returned to location

Pursued side story
```

### Negative Signals

```text
Ignored hook

Skipped dialogue

Abandoned plot

Repeatedly avoided activity
```

These influence preference estimates.

---

## 8. Favorite NPC Detection

Track:

```yaml
npc_interest:
  finn: 95
  marcus: 88
  duke_harland: 22
```

Signals:

* interactions
* references
* voluntary conversations
* follow-up questions

Useful for campaign planning.

---

## 9. Plot Interest Detection

Track:

```yaml
plot_interest:
  missing_prince: 92
  trade_war: 41
  goblin_raids: 15
```

This helps identify:

* engaging stories
* abandoned stories
* forgotten stories

---

## 10. Narrative Preference Profiles

Examples:

### Explorer

```yaml
exploration: high
```

### Detective

```yaml
mystery: high
```

### Relationship Builder

```yaml
social: high
```

### Power Seeker

```yaml
progression: high
```

Profiles are descriptive.

Not prescriptive.

---

## 11. Narrative Pacing Engine

Campaign pacing becomes measurable.

Track recent session composition.

Example:

```yaml
last_10_sessions:
  combat: 55
  social: 15
  exploration: 20
  mystery: 10
```

System can detect imbalance.

---

## 12. Pacing Recommendations

Example output:

```text
Recent sessions have focused heavily on combat.

The player appears highly engaged with mysteries.

Recommendation:
Introduce an investigative thread.
```

Recommendations only.

Never automatic.

---

## 13. Dramatic Tension Model

Track campaign tension.

Inputs:

```yaml
major_threats
player_failures
player_successes
losses
victories
```

Output:

```yaml
tension_score: 72
```

Possible states:

```text
Low

Moderate

High

Overwhelming
```

---

## 14. Story Arc Analysis

Track campaign structure.

Example:

```yaml
arc:
  beginning
  rising_action
  climax
  resolution
```

WorldKeeper helps identify:

* stalled arcs
* unresolved arcs
* prematurely resolved arcs

---

## 15. Character Growth Detection

Track how actors evolve.

Example:

```text
Lia began as fearful.

Current state:
Confident.
```

WorldKeeper can identify growth patterns automatically.

---

## 16. Milestone Detection

Not all events are equal.

Example:

```text
First Victory

Major Betrayal

Companion Death

Kingdom Founded
```

Milestones become campaign landmarks.

---

## 17. Story Memory System

Separate from world facts.

Examples:

### World Fact

```text
Finn lost an eye.
```

### Story Memory

```text
The party's first major victory.
```

Story memories become important context.

---

## 18. Campaign Analytics

New dashboard.

### Metrics

```text
Favorite NPCs

Favorite Locations

Favorite Plot Types

Most Active Factions

Most Referenced Events
```

### Purpose

Understand campaign engagement.

---

## 19. Narrative Debt Analysis

Track:

```yaml
unresolved_mysteries
abandoned_plots
outstanding_promises
```

Generate warnings.

Example:

```text
The Missing Prince plot has been inactive for 14 sessions.
```

---

## 20. Campaign Health Score

Aggregate:

```yaml
continuity
engagement
pacing
narrative_debt
tension
```

Produces:

```yaml
campaign_health: 84
```

Used as a diagnostic tool.

---

## 21. Dynamic Importance Scoring

Every entity receives an importance score.

Example:

```yaml
Finn: 95

Blackport: 90

Random Merchant: 4
```

Importance is derived from:

* references
* interactions
* plot involvement
* relationships

This becomes critical for context generation.

---

## 22. Historical Query System

Support questions like:

```text
What NPC has the player interacted with most?

Which plot generated the most engagement?

What locations are most important to the story?
```

---

## 23. New MCP Functions

### Analytics

```ts
analyze_player_preferences()

analyze_campaign_health()

analyze_narrative_debt()

analyze_story_structure()
```

### Recommendations

```ts
recommend_plot_threads()

recommend_npc_reappearances()

recommend_pacing_adjustments()
```

### Queries

```ts
get_player_profile()

get_campaign_metrics()

get_story_milestones()
```

---

## 24. Context Compiler Integration

Player preferences influence context selection.

Example:

```text
Player strongly favors companion interactions.
```

Companion-related context receives higher priority.

---

## 25. Success Criteria

This phase succeeds if:

### Engagement

Campaigns feel increasingly personalized.

### Recall

Important story moments remain relevant.

### Analytics

DMs gain meaningful campaign insights.

### Pacing

Campaign variety improves.

### Recommendations

DMs regularly use suggested hooks and reminders.

---

## 26. Future Integration

This system becomes a major input to:

### Campaign Intelligence

Improved recommendations.

### Actor Engine

Better companion development.

### Living World

World events targeted toward player interests.

### Ruleset Engine

Progression systems tuned to player preferences.

---

## 27. Product Impact

Before this expansion:

WorldKeeper understands the world.

After this expansion:

WorldKeeper understands the audience.

The system evolves from a continuity engine into a storytelling intelligence platform capable of helping create campaigns that are not only consistent, but personally meaningful to the people playing them.

