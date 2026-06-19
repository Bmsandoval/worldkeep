# Open5e Integration — Rules & Reference

**Date:** 2026-06-18  
**Status:** **In progress** — rules + compendium tools on `feat/v17-open5e-rules` ([#102](https://github.com/Bmsandoval/worldkeep/issues/102) ✅, [#103](https://github.com/Bmsandoval/worldkeep/issues/103) in PR [#111](https://github.com/Bmsandoval/worldkeep/pull/111)); #104–#106 pending.

WorldKeep remembers **campaign canon**. [Open5e](https://open5e.com/) provides a **public, documented JSON API** for D&D 5e SRD content — spells, monsters, items, conditions, and (in v2) structured **rules text**. This is the preferred way to keep an LLM honest on mechanics without D&D Beyond login, cookies, or reverse-engineered endpoints.

Related: [dndbeyond-integration.md](./dndbeyond-integration.md) — optional DDB for characters, owned books, game log.

---

## Why Open5e over D&D Beyond for rules?

| | Open5e | D&D Beyond (ddb-mcp) |
| -- | ------ | -------------------- |
| **API** | Public [api.open5e.com](https://api.open5e.com/) | Unofficial / scraped |
| **Auth** | None | Session cookie + account risk |
| **Stability** | Versioned v2 API, active OSS | Breaks without notice |
| **Rules search** | `/v2/search/?query=…` + `/v2/rules/` | SRD-only tools; paid via owned books |
| **SRD coverage** | SRD 5.1 (`srd-2014`) + SRD 5.2 (`srd-2024`) | SRD ~45 sections without login |
| **PHB / DMG full text** | **No** (SRD + licensed third-party docs only) | Yes if purchased (`ddb_read_book`) |
| **Character sheets** | No | Yes |

**Conclusion:** Use **Open5e inside WorldKeep** for default rules lookups. Add **DDB optionally** when you need live character sheets, non-SRD owned sources, or roll/game-log sync — not as the primary rules pipe.

---

## API surface (v2)

Base: `https://api.open5e.com/v2/` ([docs](https://open5e.com/api-docs))

| Endpoint | Use |
| -------- | --- |
| `GET /v2/search/?query={term}` | Cross-type keyword search (rules, spells, creatures, …) |
| `GET /v2/rules/?document__key=srd-2024` | Structured rule sections with full `desc` text |
| `GET /v2/rules/{key}/` | Single rule by key |
| `GET /v2/spells/` | Spells; filter `document__key__in=srd-2024` |
| `GET /v2/creatures/` | Monsters / NPC stat blocks |
| `GET /v2/conditions/` | Condition definitions |
| `GET /v2/magicitems/`, `/v2/weapons/`, `/v2/armor/` | Gear |
| `GET /v2/classes/`, `/v2/species/`, `/v2/backgrounds/` | Character building (SRD) |
| `GET /v2/documents/` | Source list — **filter to SRD keys for “official” only** |

**SRD document keys:**

- `srd-2014` — System Reference Document 5.1 (~227 rule sections in API)
- `srd-2024` — System Reference Document 5.2 (~56 rule sections; growing)

Open5e also hosts **third-party** OGL/CC content (Tome of Beasts, Level Up, Black Flag, etc.). For “what does Wizards’ SRD say?”, always filter:

```text
document__key__in=srd-2014,srd-2024
```

Or pick one ruleset per campaign (`srd-2014` vs `srd-2024`) to avoid mixing 2014 and 2024 wording.

---

## Accuracy

**High for SRD content** when filtered to `srd-2014` / `srd-2024`:

- Data is ingested from SRD/OGL sources; Open5e maintainers track errata (e.g. 2024 spell/monster updates).
- Structured JSON (spell level, save, damage, creature CR/AC/HP) is suitable for tool responses — less hallucination than paraphrasing from memory.

**Caveats:**

- **Not a substitute for PHB/DMG** — optional classes, feats, and many spells/items are absent from SRD.
- **SRD 2024 rules corpus is still smaller** than 2014 in the API (chapter coverage in progress).
- **Third-party docs** in search results are valid for *that* product, not “official 5e” — filter or label sources in tool output.
- **Table rulings beat book text** — WorldKeep `search_rulings` / `record_ruling` still wins for your campaign.

---

## Licensing & ops

- API software: modified MIT ([open5e-api LICENSE](https://github.com/open5e/open5e-api/blob/staging/LICENSE.md)).
- Game text: SRD/OGL — same practical boundary as any SRD-based app; Open5e does not grant PHB rights.
- **Rate limit:** generous (community docs cite ~10k req/s per IP); still cache hot lookups in-session.
- **Attribution:** credit Open5e in docs/settings if we surface their text in UI later.
- **Caching:** OK to cache SRD snippets in WorldKeep for offline prep; avoid bulk mirroring third-party paid-adjacent docs.

---

## Proposed WorldKeep MCP tools

Implement as **native WorldKeep tools** (HTTP client to `api.open5e.com`) — no extra MCP server required.

| Tool | Behavior |
| ---- | -------- |
| `search_rules_reference` | `GET /v2/search/?query=` + SRD filter; return snippets + `rule_key` | ✅ v1.7 (#102) |
| `get_rules_section` | `GET /v2/rules/{key}/` full `desc` | ✅ v1.7 (#102) |
| `get_spell` / `search_spells` | By name or search; default SRD document filter | ✅ v1.7 (#103) |
| `get_creature` / `search_creatures` | Stat block for prep/combat reference | ✅ v1.7 (#103) |
| `get_condition` | Condition text | ✅ v1.7 (#103) |

**Campaign config** (env or campaign metadata):

- `WORLDKEEP_SRD_VERSION=srd-2014|srd-2024|both` (default `srd-2014` until table picks 2024)

**MCP instruction policy:**

```text
1. search_rulings (WorldKeep) — house rules and table canon
2. search_rules_reference / get_* (Open5e) — SRD mechanics text
3. record_ruling — when the table deviates from SRD
4. DDB tools (optional external MCP) — character sheets, owned books only
```

---

## Architecture

```text
┌─────────────┐     ┌──────────────────────────────┐
│  AI client  │────▶│  WorldKeep MCP               │
└─────────────┘     │  • canon, sessions, rulings    │
                    │  • Open5e HTTP (built-in) ◀────── api.open5e.com
                    └──────────────┬───────────────┘
                                   │ optional
                                   ▼
                            ddb-mcp (characters / game log)
```

---

## Alternatives

| Option | When |
| ------ | ---- |
| **Open5e (recommended)** | Default rules/spells/monsters — public API |
| **dnd-oracle** | Offline SRD SQLite if API unavailable |
| **ddb-mcp SRD tools** | Redundant if Open5e is built-in; skip |
| **DDB owned books** | Non-SRD official text user already paid for |

---

## GitHub tracking

Epic **[#101](https://github.com/Bmsandoval/worldkeep/issues/101)** — Open5e rules tools (primary) + optional DDB.

| Issue | Scope |
| ----- | ----- |
| [#102](https://github.com/Bmsandoval/worldkeep/issues/102) | Open5e client + `search_rules_reference` / `get_rules_section` |
| [#103](https://github.com/Bmsandoval/worldkeep/issues/103) | Spells, creatures, conditions + `WORLDKEEP_SRD_VERSION` |
| [#104](https://github.com/Bmsandoval/worldkeep/issues/104) | Rules precedence in MCP instructions + README |
| [#105](https://github.com/Bmsandoval/worldkeep/issues/105) | Optional DDB character link + dual-MCP party snapshot |
| [#106](https://github.com/Bmsandoval/worldkeep/issues/106) | Spike: game-log events + map-aware canon (no VTT) |
