package mcp

const ServerInstructions = `WorldKeep is a campaign continuity engine for AI-assisted tabletop RPGs.

Hard rules during active play:
1. Call context retrieval (compile_scene_context or get_campaign_overview) BEFORE narrating.
2. Use propose_world_update → commit_world_update for canon changes — never silently overwrite stored facts.
3. Do not contradict stored canon without an explicit update tool call.
4. Treat tool output as raw structured data — WorldKeep stores; you narrate.

WorldKeep remembers; the AI reasons.`

const defaultProtocolVersion = "2025-06-18"
