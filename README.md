# WorldKeep

**Persistent world intelligence for AI-assisted tabletop RPGs and narrative play**

WorldKeep is a continuity layer between AI and long-running campaigns. It stores structured campaign memory — actors, locations, events, plots, facts, and rulings — so an AI DM can **retrieve relevant context before narrating** and **propose canon updates** as play progresses, instead of forgetting after a long session.

## Status

**Deployable MVP (v1.8.0)** — MCP continuity engine, Cognito Hosted UI + campaign dashboard, unified Docker/Fargate on `worldkeep.bsandoval.dev`, and **Open5e SRD rules tools** on `develop`.

Product spec: [docs/](./docs/) · agent entry: [HANDOFF.md](./HANDOFF.md)

## Quick start

```bash
make seed
make test-all
make serve      # Go MCP+REST :8788 + Laravel UI :8000
make mcp        # stdio — Cursor only
# Docker (UI + MCP + REST on one port):
make docker-build && docker run --rm -p 8080:80 worldkeep:local
```

## Quick concept

1. **Create a campaign** — e.g. *Shadows of Blackport*.
2. **Play with an AI client** connected via MCP (Cursor stdio first; ChatGPT via tunnel later).
3. **AI retrieves context** — `compile_scene_context`, entity lookup, search — before narrating.
4. **AI proposes updates** — `propose_world_update` → DM approves → `commit_world_update`.
5. **Resume later** — continuity survives across sessions and chats.

## Rules & reference sources

WorldKeep separates **campaign canon** from **book mechanics**. Agents should follow this order:

| Priority | Source | When |
| -------- | ------ | ---- |
| 1 | `search_rulings` + stored facts | Table/house canon — **always wins** |
| 2 | Open5e tools (built in) | SRD rules, spells, creatures, conditions — **no auth** |
| 3 | `record_ruling` | Persist deviations from SRD |
| 4 | [ddb-mcp](https://github.com/iamjameslennon/ddb-mcp) *(optional sidecar)* | Character sheets, owned books, game log — **not** primary rules |

**Open5e (default):** [`search_rules_reference`](docs/open5e-integration.md), `get_rules_section`, `search_spells`, `get_spell`, `search_creatures`, `get_creature`, `get_condition`. Filter SRD document with:

```bash
export WORLDKEEP_SRD_VERSION=srd-2014   # default
# or srd-2024 | both
```

Details: [docs/open5e-integration.md](./docs/open5e-integration.md)

**D&D Beyond (optional):** Use a second MCP server only when you need live sheets or owned PHB text. Do not duplicate SRD lookups. See [docs/dndbeyond-integration.md](./docs/dndbeyond-integration.md).

### Cursor MCP config

**WorldKeep only** (recommended) — [`.cursor/mcp.json`](./.cursor/mcp.json):

```json
{
  "mcpServers": {
    "worldkeep": {
      "command": "go",
      "args": ["run", "./cmd/worldkeep-mcp"],
      "cwd": "/path/to/worldkeep/mcp",
      "env": {
        "WORLDKEEP_DATA_DIR": "/path/to/worldkeep/data",
        "WORLDKEEP_SRD_VERSION": "srd-2014"
      }
    }
  }
}
```

**WorldKeep + optional DDB sidecar** — [`.cursor/mcp-with-ddb.example.json`](./.cursor/mcp-with-ddb.example.json) (copy paths and `DDB_COOKIE` as needed).

HTTP / ChatGPT: `make serve` or Docker exposes `/mcp` on the same host as the web UI — see [docs/deploy.md](./docs/deploy.md).

## Documentation

| Doc | Purpose |
| --- | ------- |
| [HANDOFF.md](./HANDOFF.md) | Session entry point for agents |
| [AGENTS.md](./AGENTS.md) | Agent rules + stack constraints |
| [docs/README.md](./docs/README.md) | Full documentation index |
| [docs/deploy.md](./docs/deploy.md) | Unified Docker / hosting |
| [docs/open5e-integration.md](./docs/open5e-integration.md) | SRD rules tools (primary) |
| [docs/dndbeyond-integration.md](./docs/dndbeyond-integration.md) | Optional DDB sidecar |
| [docs/poc.md](./docs/poc.md) | What to build first |
| [docs/roadmap.md](./docs/roadmap.md) | Phased delivery |

## Repo

- **GitHub:** [Bmsandoval/worldkeep](https://github.com/Bmsandoval/worldkeep)
- **Integration branch:** `develop`
- **Latest release:** `v1.8.0` (deployable MVP — Cognito prod UI)

## Maintainer gates

Do not merge PRs or cut releases unless explicitly asked.
