# MCP tools design

Tool and schema design for the Worldkeep MCP server. **v0.1** implements the *Core* set; others are staged.

## Design principles

1. **Raw JSON out** — Worldkeep never narrates; the client LLM summarizes.
2. **Upsert merge** — Updates patch fields; absent fields unchanged.
3. **Scoped reads** — Avoid returning full campaign DB.
4. **Explicit writes** — No silent extraction from chat text inside the server.
5. **Annotations** — `readOnlyHint`, `destructiveHint`, `openWorldHint: false` for ChatGPT.

## Data model (SQLite)

```text
campaigns          id, name, premise, tags, created_at, updated_at
locations          id, campaign_id, name, description, attributes_json, discovered_at
connections        id, campaign_id, from_location_id, to_location_id, label, bidirectional
entities           id, campaign_id, kind, name, description, attributes_json, visibility
entity_locations   entity_id, location_id, since_event_id (nullable)
events             id, campaign_id, summary, body, location_id, occurred_at, source
event_entities     event_id, entity_id, role
lore               id, campaign_id, title, body, importance, tags, source, created_at
player_state       campaign_id (PK), location_id, inventory_json, quests_json, attributes_json
fts_*              FTS5 virtual tables on locations, entities, lore, events
```

**Entity kinds:** `npc`, `item`, `faction`, `creature`, `other`

**Importance:** `critical`, `high`, `normal`, `minor`

**Source:** `player`, `dm`, `inferred`

## Tool catalog

### Campaign lifecycle

| Tool | v0.1 | Description |
|------|------|-------------|
| `start_campaign` | yes | New DB; requires `name` + `premise` |
| `load_campaign` | yes | Switch active campaign by name |
| `list_campaigns` | stub | List `./data/*.sqlite` names |
| `export_campaign` | v0.3 | JSON dump |
| `import_campaign` | v0.3 | JSON restore |

### Reads

| Tool | v0.1 | Description |
|------|------|-------------|
| `get_scene_context` | yes | Player location, place detail, entities here, last N local events, critical lore |
| `get_location` | yes | Named or id location + connections + entities |
| `get_entity` | yes | By name or id |
| `get_map` | v0.3 | Discovered locations + edges from current or root |
| `search_world` | yes | FTS query across locations, entities, lore, events |
| `get_player_state` | yes | Inventory, quests, location id |

### Writes

| Tool | v0.1 | Description |
|------|------|-------------|
| `move_to` | yes | Set player location; create location stub if missing |
| `upsert_location` | yes | Name, description, attributes, optional connections |
| `upsert_entity` | yes | kind, name, description, attributes; optional `at_location` |
| `record_event` | yes | summary, optional body, location, entity names |
| `add_lore` | yes | title, body, importance, tags |
| `update_player_state` | yes | Merge inventory/quests/attributes |
| `connect_locations` | v0.3 | Add exit between two places |

### Meta (v0.4)

| Tool | Description |
|------|-------------|
| `summarize_session` | Append digest lore from LLM-provided summary text |
| `check_contradiction` | Compare proposed fact to FTS hits; return warnings only |

## `get_scene_context` response shape (conceptual)

```json
{
  "campaign": {
    "name": "kanto-run",
    "premise": "We are Pokémon trainers starting in Pallet Town."
  },
  "player": {
    "location_id": "...",
    "location_name": "Pallet Town",
    "inventory": [],
    "quests": {}
  },
  "location": {
    "name": "Pallet Town",
    "description": "...",
    "connections": [{"label": "north", "to": "Route 1"}]
  },
  "entities_present": [],
  "recent_events": [],
  "relevant_lore": []
}
```

## Server instructions (summary)

Include in MCP `initialize` instructions:

- Call `get_scene_context` before each in-character reply during play.
- On movement intent, call `move_to` then `get_scene_context`.
- When introducing a persistent NPC or place detail, call `upsert_entity` / `upsert_location`.
- When something important happens, call `record_event` and optionally `add_lore`.
- New campaigns require `start_campaign` with the user's premise verbatim.

## Transport

| Mode | Entry | Client |
|------|-------|--------|
| stdio | `worldkeep-mcp` | Cursor, Claude Desktop |
| HTTP | `worldkeep-mcp --http :8788` | ChatGPT via tunnel → `POST /mcp` |

Follow Streamable HTTP patterns from timelord `mcp/`.

## Testing

- In-memory SQLite per test
- Tool handler tests without live LLM
- Fixture campaign: premise + move + entity + search round-trip
