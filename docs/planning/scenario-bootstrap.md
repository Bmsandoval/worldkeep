# Scenario bootstrap

How a campaign gets its **initial identity** — including natural-language dictation like *"we are Pokémon"*.

## User story

> As a **player**, I want to tell Worldkeep what kind of story we're in before play starts, so the model and database share the same premise and don't genericize into vanilla fantasy.

## v0.1 behavior: `start_campaign`

**Tool:** `start_campaign`

| Field | Required | Description |
|-------|----------|-------------|
| `name` | yes | Short campaign id/slug (e.g. `kanto-run`, `tavern-mystery`) |
| `premise` | yes | Free-text scenario dictation from the user |
| `template` | no | Optional built-in seed pack id (v0.3+) |

**Effects:**

1. Creates a new SQLite file `./data/<name>.sqlite` (or errors if exists).
2. Sets **`campaign.premise`** to the exact user text.
3. Writes an initial **`lore`** entry:
   - `title`: "Campaign premise"
   - `body`: the premise text
   - `importance`: `critical`
   - `tags`: extracted or passed genre tags
4. Sets **`player_state`** with null location and empty inventory.
5. Returns campaign summary JSON for the LLM to acknowledge in character.

**Server instructions (draft):**

> Before narrating in a new campaign, ensure `start_campaign` has been called with the user's stated premise. Do not invent a different genre. Treat the premise as immutable canon unless the user explicitly retcons via `add_lore` or a dedicated retcon tool.

## Example premises

| User says | Stored premise | Notes |
|-----------|----------------|-------|
| "We are Pokémon trainers starting in Pallet Town." | verbatim | Model infers creature-collection framing; **no official assets** shipped by Worldkeep |
| "Cozy murder mystery in a seaside inn, 1920s." | verbatim | Tags: `mystery`, `historical`, `cozy` |
| "Hard sci-fi salvage crew on a derelict station." | verbatim | Opening location may be created on first `move_to` |
| "Continue last week." | — | Use `list_campaigns` + `load_campaign` instead (v0.3) |

## Premise vs template

| Concept | Role |
|---------|------|
| **Premise** | User's words — always stored, always shown in `get_scene_context` when relevant |
| **Template** | Optional structured seed (default factions, tone checklist, starter locations) |

Templates **augment** premise; they do not replace it. If user says "Pokémon" but picks `blank` template, only premise + empty map apply.

## Planned template packs (v0.3+)

| Id | Name | Seeds |
|----|------|-------|
| `blank` | Blank slate | Premise only |
| `journey` | Journey fantasy | Generic "starting village", "road north", mentor NPC stub |
| `mystery` | Cozy mystery | Inn location, guest NPC slots, "crime" event placeholder |
| `salvage` | Sci-fi salvage | Station location, airlock, crew roles as entity stubs |

Templates are **user-facing framing**, not licensed settings. A "creature journey" template uses generic labels ("companion creature", "region badge") unless the user's premise names specifics.

## Genre tags

v0.1: optional comma-separated `tags` on `start_campaign` if the model supplies them.

v0.3: derive tags from premise via simple keyword list or LLM call **outside** Worldkeep (client suggests tags when calling the tool).

Tags drive:

- `search_world` boosts
- Template suggestions in web UI (later)
- Filtered lore in `get_scene_context`

## Resuming vs starting

| Action | Tool | When |
|--------|------|------|
| New story | `start_campaign` | User defines new premise |
| Resume | `load_campaign` | Same name as before |
| List saves | `list_campaigns` | User asks what's saved |

v0.1 may only support one active campaign per MCP process; v0.3 adds explicit switch.

## Retcon policy

Changing premise mid-campaign:

- v0.1: append new `lore` with tag `retcon` — do not silently edit original premise row
- v1.x: optional ` amend_premise` with audit event

## Acceptance criteria (v0.1 issue)

- [ ] `start_campaign` requires non-empty `premise`
- [ ] Premise appears in DB and in `get_scene_context` payload (`campaign.premise`)
- [ ] Second `start_campaign` with same name fails clearly
- [ ] MCP server instructions mention bootstrap requirement
- [ ] Manual test: premise *"we are Pokémon trainers"* → play 5 turns → restart MCP → load → premise still present
