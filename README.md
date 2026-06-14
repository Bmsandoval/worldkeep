# Worldkeep

**Prototype · External memory for interactive fiction and solo RPG play**

Worldkeep is a **local-first MCP server** that stores the state of a fictional world — map, NPCs, events, lore, and player position — so ChatGPT (or Cursor) can **read canon before narrating** and **write facts as play progresses**, instead of forgetting after a long session.

## Status

**Planning phase** (`v0.0.0`) — no application code yet. **Start here:** [HANDOFF.md](./HANDOFF.md)

See [docs/planning/](./docs/planning/) for strategy docs.

## Quick concept

1. **Bootstrap a scenario** — e.g. *"We are Pokémon trainers in Kanto, year one."*
2. **Play in ChatGPT** with Worldkeep connected (local MCP + tunnel for now).
3. **Model calls tools** — `move_to`, `upsert_entity`, `add_lore`, `get_scene_context` — to persist what happened.
4. **Resume later** — same campaign, same canon.

## Docs

| Doc | Purpose |
|-----|---------|
| [product-vision.md](./docs/planning/product-vision.md) | What we're building and why |
| [full-expansion-roadmap.md](./docs/planning/full-expansion-roadmap.md) | Full product vision, all directions, recommended timeline |
| [scenario-bootstrap.md](./docs/planning/scenario-bootstrap.md) | Initial premise / genre dictation ("we are pokemon") |
| [mcp-tools-design.md](./docs/planning/mcp-tools-design.md) | Tool and schema design for v0.1 |
| [local-mcp-architecture.md](./docs/planning/local-mcp-architecture.md) | Local SQLite, stdio, tunnel to ChatGPT |

## Repo

- **GitHub:** [Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep)
- **Integration branch:** `develop`

## Maintainer gates

Do not merge PRs or cut releases unless explicitly asked.
