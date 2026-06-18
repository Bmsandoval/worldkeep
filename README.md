# WorldKeep

**Persistent world intelligence for AI-assisted tabletop RPGs and narrative play**

WorldKeep is a continuity layer between AI and long-running campaigns. It stores structured campaign memory — actors, locations, events, plots, facts, and rulings — so an AI DM can **retrieve relevant context before narrating** and **propose canon updates** as play progresses, instead of forgetting after a long session.

## Status

**POC code complete** — planning docs in [docs/](./docs/), implementation through v0.6.0 on `develop`.

Product spec: [docs/](./docs/) (supersedes earlier bootstrap planning in git history).

## Quick concept

1. **Create a campaign** — e.g. *Shadows of Blackport*.
2. **Play with an AI client** connected via MCP (Cursor stdio first; ChatGPT via tunnel later).
3. **AI retrieves context** — `compile_scene_context`, entity lookup, search — before narrating.
4. **AI proposes updates** — `propose_world_update` → DM approves → `commit_world_update`.
5. **Resume later** — continuity survives across sessions and chats.

## Documentation

| Doc | Purpose |
| --- | ------- |
| [HANDOFF.md](./HANDOFF.md) | Session entry point for agents |
| [AGENTS.md](./AGENTS.md) | Agent rules + stack constraints |
| [docs/README.md](./docs/README.md) | Full documentation index |
| [docs/poc.md](./docs/poc.md) | What to build first |
| [docs/roadmap.md](./docs/roadmap.md) | Phased delivery |

## Repo

- **GitHub:** [Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep)
- **Integration branch:** `develop`

## Maintainer gates

Do not merge PRs or cut releases unless explicitly asked.
