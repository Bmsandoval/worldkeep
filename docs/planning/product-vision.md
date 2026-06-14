# Product vision

## What we're building (primary offering)

**External world memory for LLM-assisted play** — a persistent store for the fiction you're playing or writing *with* an AI, so the model doesn't forget where you are, who you met, or what already happened.

The first interface is an **MCP server** the model calls to read and write canon. The human plays in natural language; Worldkeep holds the **structured truth**.

## The problem

Long ChatGPT (or Cursor) sessions lose:

- Current location and discovered map
- NPC names, relationships, and prior dialogue facts
- Quest progress and inventory
- Foundational lore ("the king is dead", "we're in Kanto", "Team Rocket exists")

Without external memory, every reply re-invents the world slightly. Worldkeep fixes that.

## North star (container, not day one)

**Campaign studio** — one place to play, prep, review, and share fictional worlds:

- Live play via MCP (ChatGPT, Cursor, future clients)
- Web UI for map, timeline, and manual edits
- Scenario templates ("Pokémon journey", "cozy tavern mystery", blank fantasy)
- Optional mechanics packs (inventory, stats, dice) as **data layers**, not a replacement for the LLM narrator

## Prototype (current focus)

We are in the **prototype** phase (`v0.x`) — not MVP.

**Goal:** Prove the **memory loop**:

1. User dictates an initial scenario (*"we are Pokémon trainers"*).
2. Model uses MCP tools during play.
3. User closes ChatGPT, returns tomorrow — **canon persists**.

**Stack:** Go · SQLite · local stdio MCP · Streamable HTTP + **tunnel** for ChatGPT (no cloud deploy yet).

**Not in prototype:** accounts, web UI, multi-player, rules engines, cloud sync.

## MVP (future)

**MVP** means a **shippable** product for solo players and GMs — web app, campaign management, export, tunnel-free ChatGPT connector option, and production ops. Plan via **`v1.x`** milestone when prototype slices land.

## Post-prototype trajectory

See [full-expansion-roadmap.md](./full-expansion-roadmap.md) for the full fan-out. Summary:

1. **Richer MCP** — map graph, templates, conflict detection, session summaries
2. **Web companion** — browse/edit canon, visualize map and timeline
3. **GM mode** — hidden lore, player-visible subsets, session prep
4. **Mechanics packs** — optional structured stats (generic, D&D-ish, creature-collection framing)
5. **Collaboration** — shared campaigns, role-based visibility
6. **Platform** — cloud sync, template marketplace, integrations

## Architecture implication

Build **domain-agnostic** primitives early:

- `Campaign` with `premise` (bootstrap text) and `genre_tags`
- `Location` graph with connections
- `Entity` (NPC, item, faction, creature) with flexible `attributes` JSON
- `Event` log (what happened, where, involving whom)
- `Lore` entries with tags and importance
- `PlayerState` (location pointer, inventory bag, quest flags)

Keep genre-specific content in **templates** and **bootstrap prompts**, not hard-coded game logic.

## What we are not claiming today

- A replacement for Foundry, Roll20, or full VTT combat
- Official IP modules (Pokémon, etc.) — user-provided **premise** only; templates are framing, not licensed content
- Guaranteed ChatGPT App Directory listing
- Multi-user production service

## Execution vs planning

| Source | Role |
|--------|------|
| **GitHub issues** | What to implement **now** |
| **`docs/planning/`** | Why and **which stage** |

Agents: **follow the active issue**; use planning for alignment only.
