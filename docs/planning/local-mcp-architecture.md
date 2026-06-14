# Local MCP architecture

How Worldkeep runs on a developer machine and connects to ChatGPT via a tunnel.

## Components

```text
┌─────────────┐     stdio      ┌──────────────────┐     SQLite    ┌─────────────┐
│ Cursor /    │◀──────────────▶│  worldkeep-mcp   │◀─────────────▶│ ./data/     │
│ Claude      │                │  (Go binary)     │               │ *.sqlite    │
└─────────────┘                └────────┬─────────┘               └─────────────┘
                                        │ HTTP :8788
┌─────────────┐     HTTPS      ┌────────▼─────────┐
│ ChatGPT     │◀──────────────▶│ cloudflared /    │
│ (remote MCP)│   (tunnel)     │ ngrok            │
└─────────────┘                └──────────────────┘
```

## Directory layout (planned)

```text
worldkeep/
├── cmd/worldkeep-mcp/     # main entry
├── internal/
│   ├── mcp/               # protocol, tools, handlers
│   ├── store/             # SQLite repositories
│   └── domain/            # types
├── data/                  # gitignored campaign DBs
├── scripts/
│   ├── tunnel.sh          # v0.2 — expose :8788
│   └── create_github_issues.py
└── docs/planning/
```

## Configuration (planned `ex.env`)

| Variable | Default | Purpose |
|----------|---------|---------|
| `WORLDKEEP_DATA_DIR` | `./data` | Campaign SQLite files |
| `WORLDKEEP_MCP_ADDR` | `:8788` | HTTP listen address |
| `WORLDKEEP_MCP_PUBLIC_URL` | (tunnel URL) | OAuth/resource metadata later |
| `WORLDKEEP_LOG_LEVEL` | `info` | Logging |

## stdio mode (v0.1)

```bash
go run ./cmd/worldkeep-mcp
# or
make run-stdio
```

Cursor MCP config (example):

```json
{
  "mcpServers": {
    "worldkeep": {
      "command": "/path/to/worldkeep-mcp",
      "env": {
        "WORLDKEEP_DATA_DIR": "/path/to/worldkeep/data"
      }
    }
  }
}
```

## HTTP + tunnel mode (v0.2)

```bash
# Terminal 1
make run-http    # listens on :8788, POST /mcp

# Terminal 2
./scripts/tunnel.sh 8788
# prints https://xxxx.trycloudflare.com
```

ChatGPT connector setup:

1. Add MCP server URL: `https://xxxx.trycloudflare.com/mcp`
2. Transport: Streamable HTTP
3. Auth: none (prototype)

**Security note:** Tunnel exposes your local MCP to the internet while running. Stop tunnel when not playing. Do not run unauthenticated tunnel on untrusted networks long-term.

## Tunnel script options

| Tool | Pros | Cons |
|------|------|------|
| **cloudflared** (`trycloudflare.com`) | Free, no account for quick tunnel | URL changes each run |
| **ngrok** | Stable subdomain on paid tier | Account required |

v0.2 ships cloudflared-first script; document ngrok as alternative.

## Single active campaign

v0.1: process holds one `active_campaign` in memory, set by `start_campaign` / `load_campaign`.

v0.3: explicit `campaign` param on tools optional; default active.

## Why local-first

- Fast iteration on schema and tools
- No OAuth/ECS before play loop is fun
- Privacy — fiction stays on disk
- Same SQLite file later syncable to cloud (platform phase)

## Future: self-hosted HTTPS

When tunnel friction is too high, reuse prototyper infra pattern:

- Go MCP container on ECS
- ALB path `/mcp`
- Cognito OAuth per user (timelord `#49` pattern)

Not in v0.x scope unless promoted by maintainer.

## Health check

HTTP mode exposes `GET /healthz` for tunnel sanity and future deploy.
