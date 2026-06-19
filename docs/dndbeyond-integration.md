# External Rules & Reference — Investigation

**Date:** 2026-06-18 (updated)  
**Status:** Investigation complete; implementation tracked in GitHub **#101**.

WorldKeep stores **campaign continuity** (canon, sessions, rulings). For **official mechanics text**, prefer **[Open5e](https://open5e.com/)** (public API) — see [open5e-integration.md](./open5e-integration.md). **D&D Beyond** remains optional for characters, owned books, and game-log sync — see below.

---

## Recommended stack

| Layer | Source | Role |
| ----- | ------ | ---- |
| **Table canon** | WorldKeep | `search_rulings`, facts, secrets, sessions |
| **SRD rules & stat blocks** | **Open5e API** (built into WorldKeep) | Spells, monsters, conditions, rules sections — no auth |
| **Characters / paid books / rolls** | DDB via ddb-mcp (optional) | Sheets, PHB text you own, game log — unofficial |

---

## 1. There is no official D&D Beyond API

- Wizards / D&D Beyond do **not** publish a supported public API for third-party apps ([forum thread](https://www.dndbeyond.com/forums/d-d-beyond-general/d-d-beyond-feedback/148262-can-i-use-the-api-for-free), ongoing in 2026).
- Forge VTT had a **limited partnership** for character import; that is not a general developer API.
- Community tools use **reverse-engineered HTTP endpoints** and **session cookies** (`CobaltSession`). D&D Beyond staff have warned that abnormal access patterns can lead to **temporary or permanent account blocks**.

**Implication:** Any WorldKeep integration is **best-effort, unofficial, and breakable**. Design for graceful degradation and never treat DDB as the only rules source without a fallback.

---

## 2. Unofficial MCP servers (what exists)

| Server | Package / repo | Auth | Best for |
| ------ | -------------- | ---- | -------- |
| **[iamjameslennon/ddb-mcp](https://github.com/iamjameslennon/ddb-mcp)** | `npx @iamjameslennon/ddb-mcp` | Browser login once; session file | Broadest tool set — characters, campaigns, monsters, encounters, SRD rules, owned books via `ddb_read_book` |
| **[AlexWorland/dndbeyond-mcp](https://github.com/AlexWorland/dndbeyond-mcp)** | `dndbeyond-mcp` | Playwright login → `~/.dndbeyond-mcp/config.json` | Character read/update (HP, slots), campaigns, reference lookups |
| **[heffrey78/dnd-mcp](https://github.com/heffrey78/dnd-mcp)** | Open5e-based | None | SRD-like content via Open5e API — **not** DDB account |
| **[gregario/dnd-oracle](https://github.com/gregario/dnd-oracle)** | Local SQLite SRD | None | Ground-truth **SRD 5.1** only; good fallback when DDB is down |

**Recommendation for WorldKeep:** Implement **Open5e HTTP tools natively** first ([open5e-integration.md](./open5e-integration.md)). Use **ddb-mcp** only for character/party sync, owned-book lookups, and game-log events — not as the primary rules pipe.

---

## 3. What ddb-mcp can do (capabilities)

### Rules & reference (keep the LLM honest)

| Tool | Login? | Coverage | Notes |
| ---- | ------ | -------- | ----- |
| `ddb_search_rules` / `ddb_get_rules` | **No** | **SRD only** — 45 sections | Keyword search + full section text; good for Attack, Spellcasting, Conditions basics |
| `ddb_get_condition` | No | SRD conditions | |
| `ddb_search_spells` / `ddb_get_spell` | Usually yes for full DDB text | Full compendium if entitled | Depends on account + owned sources |
| `ddb_search_monsters` / `ddb_get_monster` | Yes | Monster stat blocks | |
| `ddb_read_book` | Yes | **Owned sourcebooks** | Full PHB/DMG/XGE text if purchased — best path for non-SRD rules |
| `ddb_list_library` | Yes | What you own | |

**Accuracy:**

- **SRD tools:** High for SRD content — text comes from DDB’s SRD presentation (same underlying rules as SRD 5.1). Does **not** include 2024 PHB rewording or non-SRD options unless you read owned books.
- **Character/party tools:** Generally strong — uses official character-service API JSON; maintainers test 2014 vs 2024 rule nuances (e.g. Jack of All Trades, Alert on initiative).
- **Scraped page text** (`ddb_navigate`, `ddb_get_page`): Variable; wrapped as untrusted content; risk of prompt injection from user-authored DDB fields.
- **Stability:** Endpoints change without notice; session expires; account risk if abused.

**WorldKeep overlap:** WorldKeep already has `search_rulings` / `record_ruling` for **table-specific house rules**. DDB integration should answer **“what does the book say?”**; WorldKeep answers **“what did we decide at our table?”**

### Characters & campaigns

- List/get characters, party summaries (`ddb_get_party`), campaign roster — useful to **sync PC names/IDs** with WorldKeep entities, not to replace WorldKeep canon.

### Encounters & treasure

- CR rating, treasure tables — useful for prep; orthogonal to WorldKeep continuity unless we record outcomes as events.

### Browser automation

- `ddb_navigate`, `ddb_interact`, `ddb_get_page` — headless Chrome against arbitrary DDB URLs. Powerful but fragile.

---

## 4. D&D Beyond Maps — can the LLM work with maps directly?

**Product:** [D&D Beyond Maps VTT](https://www.dndbeyond.com/posts/1570-d-d-beyond-maps-how-to-start-playing-today) (beta) — grid maps, tokens, fog of war, initiative shortcuts. Players drag tokens; DM places monsters from token browser tied to campaign characters.

**Programmatic access today:**

| Approach | Map / token support | LLM-friendly? |
| -------- | ------------------- | ------------- |
| **Official API** | None | No |
| **ddb-mcp API tools** | No map-specific tools | No structured grid |
| **ddb-mcp browser tools** | Could open Maps URL, screenshot, scrape text | **Poor** — no coordinates, no token IDs in API; canvas UI |
| **Game log WebSocket** (Foundry bridges: [ddb-game-log](https://github.com/IamWarHead/ddb-game-log), [AstralBridge](https://github.com/AlexWorland/AstralBridge)) | **Roll events**, HP sync, initiative — not map layout | Partial — “Finn rolled 18 attack”, not “Finn moved to square C4” |
| **DDB Importer → Foundry** | Full map in **Foundry**, not DDB Maps | Would need Foundry API + separate integration |

**Conclusion:**

- The LLM **cannot** reliably drive D&D Beyond Maps “somewhat directly” today except via ** brittle browser automation** (screenshots + clicks) — not suitable as a core WorldKeep feature.
- **Realistic map-adjacent integration:**
  1. **Short term:** Human moves tokens; LLM receives **narrative state** from WorldKeep (`compile_scene_context`: location, combat summary facts recorded by DM).
  2. **Medium term:** Subscribe to **game log / roll feed** (Cobalt + campaign/game ID) → record events in WorldKeep (`record_event`).
  3. **Long term:** Integrate **Foundry** or a WorldKeep-native grid if tactical play is a product goal — not DDB Maps internals.

---

## 5. Proposed WorldKeep integration architecture

```text
┌─────────────┐     ┌──────────────┐     ┌─────────────────┐
│  AI client  │────▶│  WorldKeep   │────▶│  Campaign canon │
│  (Cursor)   │     │  MCP         │     │  (SQLite)       │
└─────────────┘     └──────┬───────┘     └─────────────────┘
                           │
                           │ optional compose or proxy
                           ▼
                    ┌──────────────┐     ┌─────────────────┐
                    │  ddb-mcp     │────▶│  D&D Beyond     │
                    │  (external)  │     │  (unofficial)   │
                    └──────────────┘     └─────────────────┘
```

### Phase A — Rules honesty (recommended first)

- **Phase A0 (preferred):** Native Open5e tools in WorldKeep — see [open5e-integration.md](./open5e-integration.md).
- Document **source policy** in MCP instructions: Open5e for SRD; WorldKeep for table rulings; DDB optional for sheets/owned books.
- Optional DDB-only tools (external MCP):
  - `cite_rule_for_ruling` — attach Open5e rule key or DDB section when recording a ruling
- Config: `WORLDKEEP_SRD_VERSION`; optional `DDB_MCP` sidecar in Cursor multi-server setup.

### Phase B — Character / party sync

- Link WorldKeep `entity_id` ↔ DDB `character_id`
- On session start, pull `ddb_get_party` snapshot → compare to WorldKeep facts (HP is ephemeral; don’t overwrite canon automatically)

### Phase C — Map & combat awareness (limited)

- Ingest **roll/game log** events into WorldKeep events (not grid state)
- DM declares position in natural language → `record_event` / fact (“Finn at warehouse door”)

---

## 6. Risks & mitigations

| Risk | Mitigation |
| ---- | ---------- |
| DDB ToS / account block | Read-only where possible; rate limits; user consent; document unofficial status |
| API breakage | Abstract behind adapter; SRD fallback (dnd-oracle / Open5e) |
| LLM confuses book rules vs table rulings | Tool descriptions + WorldKeep `record_ruling` precedence in instructions |
| Paid content leakage | Only query books user owns; don’t cache PHB text in WorldKeep DB without license review |
| Map automation fragility | Do not depend on browser automation for MVP integration |

---

## 7. Alternatives summary

| Need | Best option |
| ---- | ----------- |
| SRD rules, spells, monsters (default) | **Open5e API** — built into WorldKeep |
| SRD offline fallback | **dnd-oracle** or cached Open5e responses |
| Full compendium + characters | **ddb-mcp** with user login |
| House rules at your table | **WorldKeep** `search_rulings` / `record_ruling` (already built) |
| Tactical grid + LLM | **Not DDB Maps** — Foundry bridge or future WorldKeep location model |

---

## 8. GitHub tracking

Epic: **[#101](https://github.com/Bmsandoval/worldkeep/issues/101)** — Open5e rules tools (primary) + optional D&D Beyond (characters / game log).

See also: **[open5e-integration.md](./open5e-integration.md)**
