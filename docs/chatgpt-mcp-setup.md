# ChatGPT MCP connector setup

Connect ChatGPT to a local WorldKeep campaign over HTTPS using a tunnel.

## Prerequisites

- WorldKeep seeded: `make seed`
- [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/) installed
- Go 1.23+

## 1. Start HTTP MCP + tunnel

```bash
cd ~/projects/prototyper/prototypes/worldkeep
chmod +x scripts/tunnel.sh
./scripts/tunnel.sh
```

The script:

1. Runs `worldkeep-mcp-http` on `WORLDKEEP_MCP_ADDR` (default `:8788`)
2. Opens a cloudflared quick tunnel
3. Logs the public URL to `.tunnel/cloudflared.log`

Copy the `https://*.trycloudflare.com` URL from the log.

## 2. Configure ChatGPT

In ChatGPT → **Settings → Connectors → MCP**:

| Field | Value |
|-------|-------|
| Server URL | `https://YOUR-TUNNEL.trycloudflare.com/mcp` |
| Auth | None (POC local single-user) |

Set in `local.env` if you need a stable reference:

```bash
WORLDKEEP_MCP_PUBLIC_URL=https://YOUR-TUNNEL.trycloudflare.com
```

## 3. Smoke test

Ask ChatGPT to call WorldKeep tools:

1. `get_campaign_overview`
2. `compile_scene_context` with prompt: "Party returns to Blackport"
3. `propose_world_update` then `commit_world_update`

See [playtest-notes.md](./playtest-notes.md) for the full POC checklist.

## Cursor (local stdio)

Cursor uses stdio — no tunnel required. See playtest-notes.md for MCP config.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 502 from tunnel | Ensure `make mcp-http` responds at `/healthz` locally |
| Empty campaign | Run `make seed` |
| Wrong campaign DB | Set `WORLDKEEP_CAMPAIGN_ID=campaign_001` |
