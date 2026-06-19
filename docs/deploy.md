# WorldKeep deployment (unified service)

One running service hosts **Laravel UI**, **MCP**, and **REST API**. Go listens on loopback; Apache serves the browser and reverse-proxies engine paths.

## Architecture

```text
Internet → :80 (Apache)
            ├─ /app/*           → Laravel (PHP)
            ├─ /mcp             → Go 127.0.0.1:8788
            ├─ /api/v1/*        → Go 127.0.0.1:8788
            ├─ /api/auth/*      → Laravel (Cognito)
            └─ /healthz         → Go 127.0.0.1:8788
```

Laravel calls Go server-side via `WORLDKEEP_INTERNAL_URL=http://127.0.0.1:8788` (not exposed to browsers).

## Local Docker smoke test

```bash
make seed          # optional — container auto-seeds if DB missing
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
| `WORLDKEEP_DATA_DIR` | `/var/worldkeep/data` | SQLite campaign files |
| `WORLDKEEP_HTTP_ADDR` | `127.0.0.1:8788` | Go listen (loopback only) |
| `WORLDKEEP_CAMPAIGN_ID` | `campaign_001` | Default campaign |
| `WORLDKEEP_INTERNAL_URL` | `http://127.0.0.1:8788` | Laravel → Go |
| `WORLDKEEP_API_TOKEN` | *(empty)* | Optional REST bearer auth |
| `WORLDKEEP_ROLE` | `dm` | Go write scope |
| `WORLDKEEP_SRD_VERSION` | `srd-2014` | Open5e document filter (`srd-2024`, `both`) |

Laravel uses `web/.env` (generated on first boot from `.env.example`).

Rules source policy: [open5e-integration.md](./open5e-integration.md) · [README](../README.md#rules--reference-sources)

## Persistent data

Mount a volume on `/var/worldkeep/data` for campaign SQLite:

```bash
docker run --rm -p 8080:80 \
  -v worldkeep-data:/var/worldkeep/data \
  worldkeep:local
```

Laravel session/users live in `database/database.sqlite` inside the container unless you add a separate volume for `web/database/`.

## AWS / Fargate (hub-prod)

WorldKeep runs as **`worldkeep.<domain>`** on the shared hub-prod ECS cluster (one container: Apache + Go + SQLite).

### One-time infra (maintainer)

In `infra/prod.env` — `worldkeep` entry in `TF_VAR_php_services` with `"sqlite":true` and `"cognito":false`.

```bash
source infra/profile.sh
protot plan prod service
protot deploy prod service
```

### Deploy app

```bash
source infra/profile.sh
infra/scripts/deploy-worldkeep.sh prod worldkeep-v1.8.0
```

| URL | Purpose |
| --- | ------- |
| `https://worldkeep.<domain>/app` | Web UI |
| `https://worldkeep.<domain>/mcp` | ChatGPT MCP |
| `https://worldkeep.<domain>/health` | ALB health |
| `https://worldkeep.<domain>/healthz` | Go engine health |

**Persistence:** campaign + Laravel SQLite live in the container filesystem. **Redeploys reset data** until EFS (or Postgres migration) is added.

Spin down when idle: `protot spin-down prod worldkeep`

## Local dev (two ports)

```bash
make serve
# Go :8788, Laravel :8000 — same engine wiring as Docker
```

See [rest-api.md](./rest-api.md) and [web-ui.md](./web-ui.md).
