# Owlbear Rodeo Integration — Design (Backlog)

**Date:** 2026-06-18  
**Status:** Backlog spike — not scheduled on active roadmap  
**Tracking:** GitHub **[#107](https://github.com/Bmsandoval/worldkeep/issues/107)**

WorldKeep is a **continuity engine** (canon, sessions, rulings). Owlbear Rodeo is a **lightweight browser VTT** for maps and tokens. This doc records a deep dive on Owlbear’s extension model, how it fits WorldKeep’s goal (“**LLM moves tokens; players don’t manually apply grid directions**”), comparisons with Foundry and other options, and why Owlbear is the preferred **external tactical layer** for backlog work.

Related:

- [open5e-integration.md](./open5e-integration.md) — rules reference (primary)
- [dndbeyond-integration.md](./dndbeyond-integration.md) — optional character/game-log
- [web-ui.md](./web-ui.md) — WorldKeep is not a VTT in early UI releases; Owlbear is an optional map surface

---

## 1. Product intent (maintainer)

From design discussions:

1. **LLM drives tactical updates** — when the AI says a monster moves, **tokens move on the map**; players are not asked to interpret “one down and three left.”
2. **WorldKeep stays the brain** — canon, facts, rulings, session state, Open5e rules; the VTT is an **execution/display layer**.
3. **No Foundry farm** — avoid one hosted Foundry process (and **$50 license**) per concurrently live party; avoid ECS spin-up orchestration as a **default** product requirement.
4. **Users never touch AWS** — scheduling or “start table” happens in WorldKeep; infra is invisible.
5. **Accept external SaaS for maps** — Owlbear hosts rooms; WorldKeep hosts only a small **extension** (static assets + WebSocket backend on existing Fargate).

Owlbear is **not** a replacement for WorldKeep. It is the default candidate for **optional tactical map integration** when groups want a real battle board without Foundry economics.

---

## 2. What Owlbear Rodeo is

| | |
|--|--|
| **Product** | Browser-based VTT at [owlbear.rodeo](https://www.owlbear.rodeo/) |
| **Self-host VTT?** | **No** — rooms run on Owlbear’s service |
| **Cost** | Free tier: unlimited **Basic Rooms**, 100MB storage, full extension/plugins access ([dev log](https://blog.owlbear.rodeo/owlbear-rodeo-2-0-dev-log-6/)); paid from ~$3.99/mo for **Custom Rooms** (persistent URL/name) |
| **Players** | Join via link; **anonymous players OK** (no account required) |
| **GM** | Room creator; can assign additional GMs; request-to-join (no passwords) |
| **Maps** | Image-based scenes, grid (square/hex/isometric), manual fog, dynamic fog (walls/lights) |
| **Tokens** | Images on layers (e.g. `CHARACTER`); not integrated character sheets |

**What you host:** only the **WorldKeep Owlbear extension** (`manifest.json` + JS/TS) on your Fargate/static CDN — same stack as future web UI assets.

**What you do not host:** Owlbear rooms, scene sync, or player WebSockets — Owlbear does that.

---

## 3. Extension architecture (how WorldKeep would integrate)

Owlbear loads third-party code via a **manifest URL** ([getting started](https://docs.owlbear.rodeo/extensions/getting-started/)).

```text
┌─────────────────────────────────────────────────────────────┐
│  owlbear.rodeo (SaaS)                                        │
│  Room → Scene → players' browsers                            │
│       └─ iframe: WorldKeep extension (action popover / bg)   │
│              └─ @owlbear-rodeo/sdk → OBR.scene.items.*       │
└───────────────────────────▲─────────────────────────────────┘
                            │ WebSocket (or SSE)
                            │ wss://worldkeep.example.com/owlbear/session
┌───────────────────────────┴─────────────────────────────────┐
│  WorldKeep (your Fargate — always on)                          │
│  • MCP: apply_tactical_actions / get_tactical_state            │
│  • Campaign ↔ room/scene/token ID map in SQLite              │
│  • LLM never talks to Owlbear directly                         │
└───────────────────────────▲─────────────────────────────────┘
                            │
                     Cursor / ChatGPT MCP
```

### Manifest surfaces

| Surface | Use for WorldKeep |
|---------|-------------------|
| **`background_url`** | **Critical** — keeps WebSocket + command loop alive without open popover |
| **Action popover** | GM UI: link campaign, map tokens to entities, approve AI moves |
| **Tool / tool mode** | Optional manual overrides |
| **`OBR.contextMenu`** | Optional “Send selection to WorldKeep” |

CORS: extension assets must allow origin `https://www.owlbear.rodeo` ([hosting docs](https://docs.owlbear.rodeo/extensions/tutorial-sharing-your-extension/hosting-your-extension/)).

### Command flow (LLM moves a token)

1. LLM calls WorldKeep MCP: `apply_tactical_actions({ moves: [{ token_id, to: {x,y} }] })`
2. WorldKeep validates against last known scene snapshot + records intent in session log
3. WorldKeep pushes command over WebSocket to extension **background** connected for that `room_id` + `campaign_id`
4. Extension (running as **GM**):

```typescript
await OBR.scene.items.updateItems([tokenUuid], (items) => {
  for (const item of items) {
    item.position = { x, y };
  }
});
```

5. Owlbear syncs position to **all players** in the room automatically
6. Extension calls `OBR.scene.items.getItems()` (or `onChange`) → WorldKeep updates tactical snapshot + optional `record_event`

**Players never parse grid prose** — they see the token move.

---

## 4. Deep dive — SDK capabilities vs our use case

### 4.1 Confirmed: supports our core loop

| Need | Owlbear API | Notes |
|------|-------------|-------|
| **Move tokens** | `OBR.scene.items.updateItems` | Pixel positions in scene space; use `OBR.scene.grid.snapPosition` for grid snap ([grid API](https://docs.owlbear.rodeo/extensions/apis/scene/grid/)) |
| **Read all tokens** | `getItems`, `onChange` | Filter `layer === "CHARACTER"` + `isImage` |
| **Add/remove tokens** | `addItems`, `deleteItems` | Builders: `buildImage()` on CHARACTER layer |
| **Grid-aware distance** | `grid.getDistance(from, to)` | Returns cells traversed per measurement mode (Chebyshev, Manhattan, etc.) |
| **Hide/show tokens** | `item.visible` via `updateItems` | NPC stealth / reveal |
| **Link to WorldKeep entities** | `item.metadata["com.worldkeep/entity_id"]` | [Metadata convention](https://docs.owlbear.rodeo/extensions/reference/metadata/) — prefix keys |
| **Room identity** | `OBR.room.id`, `setMetadata` | Store `worldkeep_campaign_id` (room metadata **≤ 16KB** total) |
| **Scene identity** | `OBR.scene.getMetadata` / `setMetadata` | Per-map tactical state pointer |
| **GM-only edits** | `OBR.player.getRole()` → `"GM"` | GMs can move all images; players restricted by room permissions |
| **Party presence** | `OBR.party.getPlayers`, `onChange` | Who is connected |
| **In-room messaging** | `OBR.broadcast.sendMessage` | Extension ↔ extension, **16KB** JSON cap — not a substitute for WorldKeep canon |
| **Permissions check** | `OBR.player.hasPermission("EDIT")` | Gate extension actions |

### 4.2 Partial / design-around

| Need | Reality | Mitigation |
|------|---------|------------|
| **Fog reveal by LLM** | `OBR.scene.fog` API adjusts **global** fog style/filled flag — not polygon erase ([fog API](https://docs.owlbear.rodeo/extensions/apis/scene/fog/)) | Phase 1: GM fog manually; Phase 2: dynamic fog via `OBR.scene.local` walls/lights (local-only, complex); or sync **narrative** visibility in WorldKeep only |
| **Initiative / combat tracker** | No built-in combat API in core SDK | WorldKeep owns initiative; extension optional UI; or metadata on tokens |
| **Character sheets / HP** | Tokens are images, not sheets | HP in WorldKeep facts or DDB optional integration (#105); overlay labels via `buildText()` |
| **Pathfinding** | No built-in A* in SDK | LLM sends grid cell targets; extension uses `snapPosition`; or implement simple path in extension |
| **Structured turn order** | Not native | WorldKeep session floor + MCP |

### 4.3 Hard limitations (must accept or pick Foundry)

| Limitation | Impact |
|------------|--------|
| **No server-side Owlbear API** | WorldKeep **cannot** HTTP POST to Owlbear to move tokens. **Requires** an extension client in a live browser session. |
| **GM (or GM-role) client must be online** | `background_url` runs in GM’s Owlbear tab. No fully headless AI DM on Owlbear alone. |
| **Extension host must be up** | Your Fargate serves manifest + WebSocket endpoint. |
| **Coordinates are pixels, not “square C4”** | Map `entity_id` ↔ item UUID; convert grid ↔ pixels via `snapPosition` / DPI ([grid `dpi`](https://docs.owlbear.rodeo/extensions/apis/scene/grid/)). |
| **Cannot self-host Owlbear rooms** | Dependency on owlbear.rodeo uptime/ToS; not suitable if you require full data sovereignty for **map runtime**. |
| **Rich module ecosystem** | Nowhere near Foundry (DDB Importer, etc.) |

### 4.4 Prior art

Community extensions demonstrate SDK-driven maps — e.g. [OwlBear-llm-chat](https://github.com/Agamador/OwlBear-llm-chat) (claims token move, fog, lighting via SDK + backend). Treat as **pattern reference**, not production dependency.

---

## 5. Options compared

### 5.1 Foundry VTT + API bridge

| Pros | Cons |
|------|------|
| Strongest automation (WebSocket bridge, pathfinding, combat) | **$50 license per concurrently live instance** |
| DDB Importer ecosystem | Must **self-host** Foundry on Fargate; calendar spin-up |
| Headless-ish with GM module | Heaviest ops; violates “no VTT farm” as default |

**Fit:** Power-user **optional** path ([dndbeyond-integration.md](./dndbeyond-integration.md)), not default tactical layer.

### 5.2 WorldKeep-native battle board

| Pros | Cons |
|------|------|
| One Fargate app, unlimited campaigns | You build grid, fog, assets, UX |
| Full MCP control, no third-party ToS | Not Foundry/Owlbear map quality day one |
| Best multi-tenant economics | Conflicts with “not a VTT” positioning unless scoped as “tactical view” |

**Fit:** Long-term if Owlbear dependency is undesirable; can coexist (Owlbear for pretty maps, native for simple fights).

### 5.3 D&D Beyond Maps

| Pros | Cons |
|------|------|
| Official maps + tokens for DDB users | **No map API** — LLM cannot move tokens programmatically |

**Fit:** Character/roll sync only (#106), not tactical execution.

### 5.4 Roll20

| Pros | Cons |
|------|------|
| Familiar to some groups | Pro API scripts; **no clean external LLM bridge** |

**Fit:** Poor default for WorldKeep product.

### 5.5 Owlbear + WorldKeep extension (**backlog choice**)

| Pros | Cons |
|------|------|
| **Official SDK** for token move/add/hide | GM browser must stay connected |
| **No Foundry license**; free rooms | Owlbear SaaS dependency |
| **You host only extension** on existing Fargate | Fog/combat weaker than Foundry |
| Players join normal Owlbear links | No programmatic room creation from WorldKeep API |
| Multi-party on **one WorldKeep stack** | Each party = separate Owlbear room (fine) |
| Matches “LLM executes, players watch” | |

---

## 6. Why we chose Owlbear (for backlog, not Foundry-first)

Decision for **backlog tactical integration**:

1. **Economics** — no per-table Foundry license or scheduled ECS Foundry tasks as a **requirement**.
2. **Ops** — WorldKeep Fargate stays **one always-on service**; extension is static assets + WebSocket routes.
3. **LLM execution path exists** — `updateItems` + `background_url` + WorldKeep WebSocket is a **proven pattern** (community LLM extensions).
4. **Good enough tactical fidelity** — grid snap, token images, multiplayer sync satisfy “don’t manually apply verbal grid directions.”
5. **Player UX** — anonymous join, no AWS, normal Owlbear links; GM installs WorldKeep extension once on the room.
6. **Product boundary** — WorldKeep remains continuity; Owlbear remains map — aligned with [product thesis](./product-thesis.md).

**Explicit non-goals for v1 Owlbear slice:**

- Replace Owlbear with self-hosted VTT
- Full automated fog of war driven by LLM
- Foundry-grade module stack
- Headless GM with zero browser open

Foundry stays documented as **optional BYOL** for groups that already live there.

---

## 7. Proposed WorldKeep ↔ Owlbear data model

```text
campaign
  owlbear_room_url          (optional — user pastes or Custom Room link)
  owlbear_room_id           (from extension on connect)

entity (PC/NPC)
  owlbear_item_id           (UUID on CHARACTER layer)
  metadata com.worldkeep/entity_id on item

session
  tactical_snapshot_json    (last sync from extension)
  owlbear_scene_id          (if multi-scene)

tactical_action (ephemeral, not canon until confirmed)
  move | hide | reveal | spawn
```

**Canon rule:** tactical moves update **session events**; only **committed** outcomes become facts if you use approval flow (same as `propose_world_update` pattern).

---

## 8. Proposed MCP tools (future)

| Tool | Purpose |
|------|---------|
| `link_owlbear_room` | Store room URL + verify extension connected |
| `map_entity_to_token` | Bind `entity_id` ↔ Owlbear item UUID |
| `get_tactical_state` | Tokens, positions, grid scale (from last extension sync) |
| `apply_tactical_actions` | Queue moves/spawns; extension executes on Owlbear |
| `sync_tactical_from_owlbear` | Pull full snapshot after player drags (human override) |

MCP instructions: **apply_tactical_actions** for NPC/combat moves after rules check (Open5e + rulings); LLM must not tell players to move tokens manually when extension status is `connected`.

---

## 9. Phased delivery (when promoted from backlog)

| Phase | Deliverable |
|-------|-------------|
| **Spike** | Minimal extension: connect WebSocket, log `onChange`, move one token from WorldKeep test command |
| **A** | Entity↔token map + `apply_tactical_actions` (move/hide) + GM popover “connected” status |
| **B** | MCP tools + session tactical snapshot + playtest script |
| **C** | Dashboard UI: paste room link, token mapping assistant |
| **D** | Optional: human drag → sync back; AI NPC auto-move with GM approve toggle |

Depends on: REST/WebSocket API (#82), optional v1.2 UI for link flow.

---

## 10. Risks

| Risk | Mitigation |
|------|------------|
| Owlbear API changes | Pin SDK version; thin adapter in extension |
| GM closes tab | Show “tactical link disconnected”; queue actions until reconnect |
| Extension CORS/CDN misconfig | Document deploy checklist on Fargate |
| LLM sends bad coordinates | Snap to grid; validate against last snapshot; GM approve mode |
| Owlbear outage | WorldKeep narrative mode + `record_event` positions; no hard dependency for canon |

---

## 11. Open questions (for spike #107)

- [ ] Confirm `background_url` receives WebSocket traffic when popover closed (browser tab still open)
- [ ] Best grid snap workflow: LLM sends cell `(col,row)` vs pixels
- [ ] Custom Room ($3.99) vs Basic Room for campaign persistence — document recommendation for GMs
- [ ] Single WorldKeep WebSocket per room vs per GM connection
- [ ] Whether to pursue dynamic fog or defer indefinitely

---

## 12. References

- [Owlbear extensions — Getting started](https://docs.owlbear.rodeo/extensions/getting-started/)
- [Scene items API](https://docs.owlbear.rodeo/extensions/apis/scene/items/)
- [Grid API](https://docs.owlbear.rodeo/extensions/apis/scene/grid/)
- [Player / GM role](https://docs.owlbear.rodeo/extensions/apis/player/)
- [Room metadata](https://docs.owlbear.rodeo/extensions/apis/room/)
- [Permissions (user-facing)](https://docs.owlbear.rodeo/docs/permissions/)
- [Rooms](https://docs.owlbear.rodeo/docs/rooms/)
- [SDK llms.txt (Context7 mirror)](https://context7.com/owlbear-rodeo/sdk/llms.txt)
