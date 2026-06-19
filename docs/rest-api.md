# WorldKeep REST API (v1.1.0)

HTTP JSON API for the web UI. Shares **`internal/store`** with MCP — no duplicate canon logic.

**Run locally (unified with MCP):**

```bash
make seed
make serve
# Go: http://127.0.0.1:8788 — MCP /mcp, REST /api/v1, health /healthz
# Web UI: http://127.0.0.1:8000/app (Laravel calls Go on 127.0.0.1:8788 server-side)
```

**Production (one container, one bill):**

```bash
make docker-build
docker run --rm -p 8080:80 worldkeep:local
# Browser UI on :8080 — Apache proxies /mcp, /api, /healthz to Go on loopback
```

**Binary:** `mcp/cmd/worldkeep-serve` (preferred). `worldkeep-api` and `worldkeep-mcp-http` are thin wrappers around the same unified server.

---

## Environment

| Variable | Default | Purpose |
| -------- | ------- | ------- |
| `WORLDKEEP_DATA_DIR` | `./data` | SQLite campaign files |
| `WORLDKEEP_CAMPAIGN_ID` | `campaign_001` (Blackport seed) | Default campaign when route omits context |
| `WORLDKEEP_HTTP_ADDR` | `:8788` | Unified listen address (MCP + REST) |
| `WORLDKEEP_MCP_ADDR` | *(fallback)* | Legacy alias for `WORLDKEEP_HTTP_ADDR` |
| `WORLDKEEP_API_ADDR` | *(fallback)* | Legacy alias for `WORLDKEEP_HTTP_ADDR` |
| `WORLDKEEP_ROLE` | `dm` | `owner` \| `dm` \| `player` — gates commit + `dm` scope |
| `WORLDKEEP_API_TOKEN` | *(empty)* | If set, require `Authorization: Bearer <token>` on REST (MCP exempt) |
| `WORLDKEEP_INTERNAL_URL` | `http://127.0.0.1:8788` | Laravel → Go base URL (web UI only) |

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

Laravel uses `App\Services\WorldKeepClient` → `WORLDKEEP_INTERNAL_URL` (never exposed to the browser). In the unified Docker image, Apache serves PHP on `:80` and reverse-proxies `/mcp`, `/api`, and `/healthz` to Go on `127.0.0.1:8788` so **one Fargate task** hosts UI + MCP + REST.

See [web-ui.md](./web-ui.md).
