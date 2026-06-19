# ChatGPT MCP connector setup

Connect ChatGPT to WorldKeep over HTTPS. Production uses **Cognito OAuth** on `POST /mcp` — see [chatgpt-mcp-oauth.md](./chatgpt-mcp-oauth.md) for the full OAuth checklist.

## Prerequisites

- WorldKeep seeded: `make seed`
- Public HTTPS URL (prod deploy or tunnel)
- Hub Cognito env vars when testing OAuth locally

## Production (recommended)

| Field | Value |
|-------|-------|
| Server URL | `https://worldkeep.bsandoval.dev/mcp` |
| Auth | OAuth — manual Cognito client (see [chatgpt-mcp-oauth.md](./chatgpt-mcp-oauth.md)) |

Optional env (Fargate usually derives these from `APP_URL`):

```bash
WORLDKEEP_MCP_PUBLIC_URL=https://worldkeep.bsandoval.dev/mcp
```

## Local tunnel (optional)

```bash
cd ~/projects/prototyper/prototypes/worldkeep
make seed
make serve   # Laravel :8000 — /mcp, /api, /app
# In another terminal, tunnel :8000 with cloudflared or similar
```

Use the tunnel URL as MCP server URL and set:

```bash
WORLDKEEP_MCP_PUBLIC_URL=https://YOUR-TUNNEL.trycloudflare.com/mcp
```

OAuth linking still requires valid Cognito client config and adding ChatGPT's redirect URI to the pool.

## Smoke test

After OAuth linking in ChatGPT:

1. `get_campaign_overview`
2. `compile_scene_context` with prompt: "Party returns to Blackport"
3. `record_event` or `propose_world_update` (canon write tools)

See [playtest-notes.md](./playtest-notes.md) for the full POC checklist.

## Cursor (local stdio)

Cursor uses stdio — no tunnel or OAuth required. `make mcp` → `php artisan worldkeep:mcp`.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| "MCP server does not implement OAuth" | Deploy build with metadata routes; verify `GET /.well-known/oauth-protected-resource/mcp` |
| Tool calls prompt sign-in loop | Register ChatGPT redirect URI on Cognito; check scopes `openid email` |
| 502 from tunnel | Ensure `curl http://127.0.0.1:8000/healthz` works |
| Empty campaign | Run `make seed` |
