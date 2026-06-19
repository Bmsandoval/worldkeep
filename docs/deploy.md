# WorldKeep deployment (unified service)

One running service hosts **Laravel UI**, **MCP**, and **REST API** in a single PHP process behind Apache.

## Architecture

```text
Internet → :80 (Apache → Laravel)
            ├─ /app/*           → Web UI (Cognito auth)
            ├─ /api/auth/*      → Cognito session API
            ├─ /api/v1/*        → WorldKeep REST (Engine)
            ├─ /mcp             → WorldKeep MCP (JSON-RPC)
            ├─ /health          → Laravel health
            └─ /healthz         → Engine health
```

Campaign canon and Laravel auth/session tables share one database (`sqlite` locally, `pgsql` on Aurora in prod).

## Local Docker smoke test

```bash
make seed          # optional — container auto-seeds on boot
make docker-build
docker run --rm -p 8080:80 worldkeep:local
```

| URL | Purpose |
| --- | ------- |
| http://localhost:8080/app | Web UI (register/login, dashboard, approvals) |
| http://localhost:8080/mcp | ChatGPT MCP connector |
| http://localhost:8080/api/v1/... | REST API |
| http://localhost:8080/healthz | Liveness |

## Environment

| Variable | Default (container) | Purpose |
| -------- | ------------------- | ------- |
| `DB_CONNECTION` | `sqlite` (local image) | Database driver |
| `WORLDKEEP_CAMPAIGN_ID` | `campaign_001` | Default campaign |
| `WORLDKEEP_API_TOKEN` | *(empty)* | Optional REST bearer auth |
| `WORLDKEEP_ROLE` | `owner` | Write scope for Engine |
| `WORLDKEEP_SRD_VERSION` | `srd-2014` | Open5e document filter (`srd-2024`, `both`) |

Laravel uses `web/.env` (generated on first boot from `.env.example`).

Rules source policy: [open5e-integration.md](./open5e-integration.md) · [README](../README.md#rules--reference-sources)

## Persistent data

**Local Docker:** mount `database/database.sqlite` or use a volume on `web/database/` for SQLite persistence.

**Production (hub-prod):** Aurora PostgreSQL database `worldkeep` — campaign + Laravel tables persist across redeploys.

## AWS / Fargate (hub-prod)

WorldKeep runs as **`worldkeep.<domain>`** on the shared hub-prod ECS cluster (PHP container + Aurora).

### One-time infra (maintainer)

In `infra/prod.env` — `worldkeep` entry in `TF_VAR_php_services` with `"cognito":true` (no `sqlite` flag).

```bash
source infra/profile.sh
protot plan prod service
protot deploy prod service
```

### Deploy app

```bash
source infra/profile.sh
infra/scripts/deploy-worldkeep.sh prod worldkeep-v1.9.0
```

| URL | Purpose |
| --- | ------- |
| `https://worldkeep.<domain>/app` | Web UI |
| `https://worldkeep.<domain>/mcp` | ChatGPT MCP |
| `https://worldkeep.<domain>/health` | ALB health |
| `https://worldkeep.<domain>/healthz` | Engine health |

Spin down when idle: `protot spin-down prod worldkeep`

## Local dev

```bash
make seed
make serve   # php artisan serve on :8000
make mcp     # stdio MCP for Cursor
```

See [rest-api.md](./rest-api.md) and [web-ui.md](./web-ui.md).
