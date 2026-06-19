# WorldKeep REST API (v1.1.0)

HTTP JSON API for the web UI and external clients. Shares **`App\Services\WorldKeep\Store`** with MCP — no duplicate canon logic.

**Run locally:**

```bash
make seed
cd web && php artisan serve
# Laravel :8000 — /app UI, /api/v1 REST, /mcp HTTP, /healthz
```

**Production (one PHP container):**

```bash
make docker-build
docker run --rm -p 8080:80 worldkeep:local
# Browser UI, MCP, and REST on :8080 (Apache → Laravel)
```

**Stdio MCP (Cursor):** `make mcp` → `php artisan worldkeep:mcp`

---

## Environment

| Variable | Default | Purpose |
| -------- | ------- | ------- |
| `DB_CONNECTION` | `sqlite` (local) / `pgsql` (prod) | Laravel + WorldKeep tables share one database |
| `WORLDKEEP_CAMPAIGN_ID` | `campaign_001` (Blackport seed) | Default campaign when route omits context |
| `WORLDKEEP_ROLE` | `owner` | `owner` \| `dm` \| `player` — gates commit + `dm` scope |
| `WORLDKEEP_API_TOKEN` | *(empty)* | If set, require `Authorization: Bearer <token>` on REST (MCP uses Cognito OAuth instead) |
| `WORLDKEEP_MCP_PUBLIC_URL` | `{APP_URL}/mcp` | RFC 9728 `resource` id for ChatGPT OAuth |
| `WORLDKEEP_COGNITO_ISSUER` | *(from pool)* | Override Cognito issuer in protected-resource metadata |
| `WORLDKEEP_SRD_VERSION` | `srd-2014` | Open5e document filter |

See [ex.env](../ex.env) and issue **#83** for auth stub details.

---

## Routes

Base path: `/api/v1`

### Health

| Method | Path | Description |
| ------ | ---- | ----------- |
| GET | `/healthz` | Liveness (`{"status":"ok"}`) — no auth |

### Campaign dashboard

| Method | Path | Query | MCP equivalent |
| ------ | ---- | ----- | -------------- |
| GET | `/campaigns/{campaign_id}/dashboard` | `scope` (party\|dm), `event_limit` | `get_campaign_dashboard` |

Response includes: `campaign`, `open_session`, `active_plots`, `recent_events`, `pending_updates` (with conflict warnings), `continuity_warnings`, `pending_update_count`, `scope`.

### Entities

| Method | Path | Query | MCP equivalent |
| ------ | ---- | ----- | -------------- |
| GET | `/entities/{entity_id}` | `scope` | `get_entity` |
| GET | `/campaigns/{campaign_id}/entities` | `type` (npc, location, …), `scope` | `get_campaign_overview` (partial) |

### Search

| Method | Path | Query | MCP equivalent |
| ------ | ---- | ----- | -------------- |
| GET | `/campaigns/{campaign_id}/search` | `q` (required), `limit`, `scope`, `hybrid` (true/false) | `search_world` |

### Sessions (v1.3.0)

| Method | Path | Query | MCP equivalent |
| ------ | ---- | ----- | -------------- |
| GET | `/campaigns/{campaign_id}/sessions` | `limit` (default 20) | — |
| GET | `/sessions/{session_id}` | — | `get_session` |

Session workspace response: `session`, `events`, `modified_entities`.

### Pending updates (approval queue)

| Method | Path | Body | MCP equivalent |
| ------ | ---- | ---- | -------------- |
| GET | `/campaigns/{campaign_id}/pending-updates` | — | `list_pending_updates` |
| POST | `/campaigns/{campaign_id}/pending-updates` | `{ "changes": [...], "reason": "..." }` | `propose_world_update` |
| POST | `/pending-updates/{update_id}/commit` | `{ "session_id": "..." }` optional | `commit_world_update` |
| POST | `/pending-updates/{update_id}/reject` | `{ "reason": "..." }` | `reject_world_update` |

**Commit:** If `session_id` is omitted, attaches changes to the campaign’s **open session** when one exists (same behavior as MCP when a session is active).

**Roles:** `player` cannot use `scope=dm` or call commit.

---

## Errors

JSON body:

```json
{ "error": { "code": "not_found", "message": "..." } }
```

HTTP status mirrors MCP-style errors: `400`, `403`, `404`, `500`.

---

## Web UI integration

Laravel uses `App\Services\WorldKeepClient` → in-process `Engine` (never exposed to the browser). The Docker/Fargate image is **PHP-only** — Apache serves Laravel on `:80`; MCP and REST are Laravel routes on the same host.

See [web-ui.md](./web-ui.md).
