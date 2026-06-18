# WorldKeeper Ruleset Engine

# Custom RPG System Generation & Management

## 1. Purpose

The Ruleset Engine allows WorldKeeper to support arbitrary tabletop systems, progression models, character archetypes, and custom game mechanics.

Instead of assuming a predefined system such as:

* D&D
* Pathfinder
* Pokemon
* Call of Cthulhu

WorldKeeper allows campaigns to define their own rules.

The system should support:

* traditional RPGs
* narrative RPGs
* custom settings
* hybrid systems
* AI-generated systems

without requiring application code changes.

---

## 2. Product Vision

A user should be able to say:

```text
I want to play as a pirate captain.
```

or

```text
I woke up as a Pokemon.
```

or

```text
I am a young dragon building a kingdom.
```

and WorldKeeper should generate:

* progression systems
* abilities
* resources
* advancement paths
* balancing guidelines

automatically.

---

## 3. Design Philosophy

### World First

Rules exist to support the world.

Not the other way around.

### Data Driven

Rules should be stored as data.

Not application code.

### AI Interpretable

Rules must be understandable by LLMs.

### Extensible

Users should be able to create new mechanics without modifying software.

---

## 4. Core Architecture

Current WorldKeeper:

```text
Campaign
World
Actors
Plots
Events
```

Expanded:

```text
Campaign
Ruleset
World
Actors
Plots
Events
```

Every campaign has exactly one active ruleset.

---

## 5. Ruleset Object

Top-level definition.

Example:

```json
{
  "id": "pokemon_isekai",
  "name": "Pokemon Isekai",
  "description": "Players awaken as Pokemon.",
  "progression_model": "evolution",
  "power_scale": "heroic"
}
```

Rulesets contain:

* attributes
* resources
* abilities
* progression systems
* advancement rules
* balance rules

---

## 6. Attributes

Attributes describe actor capabilities.

Examples:

### D&D

```yaml
strength
dexterity
constitution
intelligence
wisdom
charisma
```

### Pokemon

```yaml
attack
defense
special_attack
special_defense
speed
```

### Pirate Captain

```yaml
leadership
navigation
reputation
combat
```

### Kingdom Builder

```yaml
authority
wealth
influence
strategy
```

Attributes are defined by the ruleset.

---

## 7. Resources

Resources track expendable values.

Examples:

### D&D

```yaml
hit_points
spell_slots
```

### Pokemon

```yaml
hp
pp
```

### Pirate Captain

```yaml
crew_loyalty
supplies
ship_integrity
```

### Kingdom

```yaml
gold
food
stability
```

Resources support:

```yaml
current
maximum
recovery_rules
```

---

## 8. Ability System

Abilities become data objects.

Example:

```yaml
ability:
  name: Fireball

  tier: 3

  cost:
    mana: 5

  effects:
    - fire_damage

  tags:
    - fire
    - area
```

Abilities should be composable.

Effects become reusable building blocks.

---

## 9. Effect Library

Effects represent atomic game actions.

Examples:

```yaml
damage
heal
summon
move
buff
debuff
transform
reveal
charm
```

Abilities combine effects.

Example:

```yaml
Fireball

effects:
  damage
  burn
```

---

## 10. Progression Models

Rulesets choose progression systems.

### Level-Based

```yaml
progression:
  type: level
```

Example:

D&D

---

### Skill-Based

```yaml
progression:
  type: skill
```

Example:

RuneQuest

---

### Evolution-Based

```yaml
progression:
  type: evolution
```

Example:

Pokemon

---

### Reputation-Based

```yaml
progression:
  type: reputation
```

Example:

Pirate Captain

---

### Narrative-Based

```yaml
progression:
  type: milestone
```

Example:

Story-focused games

---

### Hybrid

```yaml
progression:
  type: hybrid
```

Multiple systems simultaneously.

---

## 11. Advancement Trees

Rulesets may define unlock paths.

Example:

```yaml
Pirate Captain

unlocks:

Crew Size 10
 -> First Mate

Crew Size 25
 -> Brigantine

Crew Size 100
 -> Fleet Commander
```

Example:

```yaml
Pokemon

Charmander
 -> Charmeleon
 -> Charizard
```

---

## 12. Archetype System

Rulesets can define archetypes.

Example:

```yaml
archetype:
  name: Pirate Captain
```

Starting package:

```yaml
resources:
  ship
  crew

abilities:
  command_crew
  inspire_crew
```

---

## 13. Balance Framework

Perfect balance is not the goal.

Consistency is.

### Power Tiers

```yaml
Tier 1
Ordinary

Tier 2
Heroic

Tier 3
Superhuman

Tier 4
Legendary

Tier 5
World-Shaping
```

Every ability receives a tier.

---

Example:

```yaml
Sword Slash

Tier 1
```

---

Example:

```yaml
Destroy City

Tier 5
```

---

## 14. Campaign Power Scale

Campaigns define a maximum power level.

Example:

```yaml
campaign_tier:
  2
```

Validation:

```text
Ability Tier 4 detected.

Exceeds campaign power scale.
```

---

## 15. AI Balance Advisor

WorldKeeper can evaluate content.

Example:

```text
New Ability:

Summon Kraken
```

Output:

```text
Power Estimate

Tier 4

Recommendation

Not appropriate for Tier 2 campaign.
```

Recommendations only.

Never forced.

---

## 16. Character Templates

Allow AI generation.

User:

```text
I want to play a pirate captain.
```

WorldKeeper generates:

```yaml
attributes:
  leadership
  combat
  navigation

resources:
  crew
  morale
  supplies

abilities:
  command_crew
  broadside
```

---

## 17. Ruleset Generation

Major future feature.

User describes world.

Example:

```text
Pokemon meets One Piece.
```

WorldKeeper generates:

```yaml
progression:
  evolution

resources:
  crew
  ship
  hp
  pp

attributes:
  attack
  defense
  leadership
```

Then requests approval.

---

## 18. Ruleset Validation

Before activation:

WorldKeeper checks:

* missing resources
* invalid references
* broken progression chains
* unreachable abilities
* tier mismatches

---

## 19. Rules Memory

Rules become first-class canon.

Stored separately from:

* world facts
* actor memories
* campaign history

Questions become:

```text
What are the evolution requirements?

How does naval combat work?

What is the crew morale penalty?
```

without relying on GPT memory.

---

## 20. New MCP Functions

### Rulesets

```ts
create_ruleset()

get_ruleset()

update_ruleset()

validate_ruleset()
```

### Abilities

```ts
create_ability()

update_ability()

get_ability()
```

### Progression

```ts
create_progression_model()

advance_actor()

get_advancement_options()
```

### Balance

```ts
evaluate_balance()

estimate_power_tier()

recommend_adjustments()
```

### Context

```ts
prepare_rules_context()

prepare_actor_capabilities()
```

---

## 21. Campaign Creation Flow

### Step 1

User describes campaign.

Example:

```text
Pokemon pirate adventure.
```

### Step 2

WorldKeeper generates draft ruleset.

### Step 3

User reviews.

### Step 4

Ruleset validated.

### Step 5

Campaign begins.

---

## 22. Integration with Other Systems

### Actors

Actors reference:

```yaml
attributes
resources
abilities
```

defined by ruleset.

### World Engine

World simulation uses:

```yaml
resources
progression
```

from ruleset.

### Campaign Intelligence

Continuity validation uses ruleset definitions.

### Living World

Faction simulations use ruleset mechanics.

---

## 23. Success Criteria

The Ruleset Engine succeeds if:

### Flexibility

Supports radically different campaign types.

### Consistency

AI applies rules consistently.

### Extensibility

Users can create systems without coding.

### Balance

Grossly overpowered content is detected.

### Interoperability

Works with all WorldKeeper systems.

---

## 24. Product Impact

Before the Ruleset Engine:

WorldKeeper supports campaigns.

After the Ruleset Engine:

WorldKeeper supports game systems.

Campaigns become one application of a broader platform capable of generating, managing, and enforcing entirely custom RPG experiences.

