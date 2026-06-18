package mcp

const ServerInstructions = `WorldKeep is a campaign continuity engine for AI-assisted tabletop RPGs.

Hard rules during active play:
1. Call context retrieval (prepare_session_brief, get_campaign_dashboard, compile_scene_context, or get_campaign_overview) BEFORE narrating.
2. Default to party scope — use scope=dm only for DM-only prep (never for player-facing narration).
3. Use propose_world_update → commit_world_update for canon changes — never silently overwrite stored facts.
4. Do not contradict stored canon without an explicit update tool call.
5. Treat tool output as raw structured data — WorldKeep stores; you narrate.

WorldKeep remembers; the AI reasons.`

const defaultProtocolVersion = "2025-06-18"
