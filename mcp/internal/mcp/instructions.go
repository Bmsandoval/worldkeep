package mcp

const ServerInstructions = `WorldKeep is a campaign continuity engine for AI-assisted tabletop RPGs.

Hard rules during active play:
1. Call context retrieval (prepare_session_brief, get_campaign_dashboard, compile_scene_context, or get_campaign_overview) BEFORE narrating.
2. Default to party scope — use scope=dm only for DM-only prep (never for player-facing narration).
3. Use propose_world_update → commit_world_update for canon changes — never silently overwrite stored facts.
4. Do not contradict stored canon without an explicit update tool call.
5. Treat tool output as raw structured data — WorldKeep stores; you narrate.

Rules & mechanics precedence (always in this order):
1. search_rulings and stored campaign facts — table canon wins over book text.
2. WorldKeep Open5e tools — SRD mechanics (no auth; filtered by WORLDKEEP_SRD_VERSION):
   - search_rules_reference / get_rules_section — rules chapters
   - search_spells / get_spell — spell text
   - search_creatures / get_creature — stat blocks
   - get_condition — condition definitions
3. record_ruling — when the table deviates from SRD or you need a durable house rule.
4. Optional external ddb-mcp (if configured) — character sheets, owned PHB/DMG text, game log only; never replace step 1; do not use ddb-mcp for SRD lookups WorldKeep already provides.

WorldKeep remembers; the AI reasons.`

const defaultProtocolVersion = "2025-06-18"
