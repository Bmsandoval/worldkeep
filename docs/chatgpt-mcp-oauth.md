# ChatGPT MCP — OAuth 2.1 setup

How ChatGPT links a **WorldKeep (Cognito) account** to the in-process Laravel MCP
server at `POST /mcp` so connector tools require a signed-in user.

Technical listing copy: [chatgpt-app-listing.md](./chatgpt-app-listing.md) ·
spec: [OpenAI Apps SDK auth](https://developers.openai.com/apps-sdk/build/auth) (RFC 9728 + OAuth 2.1 + PKCE).

Unlike Timelord, WorldKeep serves MCP **inside the same PHP/Laravel container** as the
web UI — no Go MCP sidecar or separate ECS service.

---

## What ChatGPT expects

1. **Protected-resource metadata** at `GET /.well-known/oauth-protected-resource`
   (RFC 9728 path suffix: `/.well-known/oauth-protected-resource/mcp`) →
   `resource`, `authorization_servers`, `scopes_supported`.
   ✅ `McpOAuthMetadataController` on Laravel.
2. **Authorization-server metadata** — ChatGPT fetches the issuer's
   `/.well-known/openid-configuration`.
3. **Client registration** — CIMD (preferred), DCR, **or a manually configured client**.
4. **Authorization-code flow + PKCE `S256`**, redirecting to
   `https://chatgpt.com/connector/oauth/{callback_id}`.
5. **Runtime auth trigger** — both a `WWW-Authenticate` header **and**
   `_meta["mcp/www_authenticate"]` on the failing tool result.
   ✅ `McpController` + `Protocol::toolAuthError`.
6. **Per-tool `securitySchemes`** (`oauth2`). ✅ every entry in `ToolDefinitions`.
7. **Token validation** — Cognito access or ID token (`iss`, signature, `exp`, `client_id`).

## Cognito limitations (same as Timelord)

| ChatGPT wants | Cognito | Our approach |
|---|---|---|
| DCR or CIMD | ❌ neither | **Manual OAuth client** in ChatGPT |
| `code_challenge_methods_supported: ["S256"]` in discovery | ❌ omitted | Manual config; PKCE S256 still works |
| RFC 8707 `resource` → token `aud` | ❌ not supported | Validate `iss` + signature + `exp` + `client_id` |

**Decision: configure the connector with a manual OAuth client.**

---

## Production values (hub-prod)

| Field | Value |
|---|---|
| MCP URL | `https://worldkeep.bsandoval.dev/mcp` |
| Metadata | `https://worldkeep.bsandoval.dev/.well-known/oauth-protected-resource/mcp` |
| Issuer | From hub-prod foundation (`COGNITO_ISSUER` / pool `us-east-1_zSJdGOns8`) |
| Authorization URL | `https://auth.bsandoval.dev/oauth2/authorize` |
| Token URL | `https://auth.bsandoval.dev/oauth2/token` |
| Client ID | Shared hub-prod web client (has secret) |
| Scopes | `openid email` |

Optional env overrides (usually derived from `APP_URL` + Cognito config):

```bash
WORLDKEEP_MCP_PUBLIC_URL=https://worldkeep.bsandoval.dev/mcp
WORLDKEEP_COGNITO_ISSUER=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_zSJdGOns8
```

---

## Setup steps (ChatGPT developer mode)

1. **Deploy** WorldKeep to public HTTPS (`https://worldkeep.bsandoval.dev/mcp`).
2. In **ChatGPT → Settings → Connectors → Add custom connector**:
   - MCP URL: `https://worldkeep.bsandoval.dev/mcp`
   - Auth: **OAuth** — enter Cognito Authorization URL, Token URL, Client ID, Client Secret, scopes `openid email`.
3. ChatGPT shows its **redirect URI** (`https://chatgpt.com/connector/oauth/{id}`).
   Add it to the Cognito app client's **Allowed callback URLs** (include all existing callbacks when updating — `update-user-pool-client` replaces the list).
4. Complete account linking in ChatGPT → tokens sent as `Authorization: Bearer` on every MCP `tools/call`.

Smoke test after linking: ask ChatGPT to call `get_campaign_overview`.

---

## Local dev

Metadata and auth challenges work on `make serve` (`http://127.0.0.1:8000/mcp`) when Cognito env vars are set (hub-local pool). ChatGPT still needs a public HTTPS URL — use a tunnel or deploy to prod for end-to-end linking.

See also: [chatgpt-mcp-setup.md](./chatgpt-mcp-setup.md).

## Status

- ✅ Laravel: protected-resource metadata, `securitySchemes`, `_meta` auth trigger, Cognito bearer on `tools/call`.
- ⬜ Register ChatGPT redirect URI on hub-prod Cognito client (needs per-connector id).
- ⬜ Redeploy prod after merge (e.g. `worldkeep-v1.9.1`).
