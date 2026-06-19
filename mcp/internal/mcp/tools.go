package mcp

func strProp(desc string) map[string]any {
	return map[string]any{"type": "string", "description": desc}
}

func intProp(desc string) map[string]any {
	return map[string]any{"type": "integer", "description": desc}
}

func objProp(desc string) map[string]any {
	return map[string]any{"type": "object", "description": desc}
}

func tool(name, desc string, props map[string]any, required []string, readOnly, destructive bool) map[string]any {
	if props == nil {
		props = map[string]any{}
	}
	schema := map[string]any{"type": "object", "properties": props}
	if len(required) > 0 {
		schema["required"] = required
	}
	return map[string]any{
		"name":        name,
		"description": desc,
		"inputSchema": schema,
		"annotations": map[string]any{
			"title":           name,
			"readOnlyHint":    readOnly,
			"destructiveHint": destructive,
			"openWorldHint":   false,
		},
	}
}

func boolProp(desc string) map[string]any {
	return map[string]any{"type": "boolean", "description": desc}
}

func toolDefs() []map[string]any {
	campaignID := strProp("Campaign id (default: campaign_001)")
	limit := intProp("Max results (default 10)")
	scope := strProp("Read scope: party (default) or dm")
	hybrid := boolProp("Use hybrid token search (MVP stub; default false)")

	return []map[string]any{
		tool("get_campaign_dashboard",
			"Campaign health snapshot: open session, active plots, recent events, pending approvals, continuity warnings.",
			map[string]any{"campaign_id": campaignID, "event_limit": limit, "scope": scope}, nil, true, false),
		tool("prepare_session_brief",
			"Focused session prep — active plots, recent events, open session, pending approvals, continuity warnings.",
			map[string]any{"campaign_id": campaignID, "event_limit": limit, "scope": scope}, nil, true, false),
		tool("get_campaign_overview",
			"Campaign summary, active plots, and major actors.",
			map[string]any{"campaign_id": campaignID, "scope": scope}, nil, true, false),
		tool("get_entity",
			"Return one full entity by id.",
			map[string]any{"entity_id": strProp("Entity id"), "scope": scope}, []string{"entity_id"}, true, false),
		tool("search_world",
			"Search entities and facts by keyword.",
			map[string]any{"campaign_id": campaignID, "query": strProp("Search text"), "limit": limit, "scope": scope, "hybrid": hybrid},
			[]string{"query"}, true, false),
		tool("compile_scene_context",
			"Primary context endpoint — relevant actors, facts, events, plots for a scene prompt.",
			map[string]any{
				"campaign_id": campaignID,
				"prompt":      strProp("Natural-language scene prompt"),
				"limit":       limit,
				"scope":       scope,
				"hybrid":      hybrid,
			}, []string{"prompt"}, true, false),
		tool("get_recent_events",
			"Recent campaign events.",
			map[string]any{"campaign_id": campaignID, "limit": limit}, nil, true, false),
		tool("get_active_plots",
			"Unresolved active plots.",
			map[string]any{"campaign_id": campaignID, "scope": scope}, nil, true, false),
		tool("search_rulings",
			"Search prior house rulings.",
			map[string]any{"campaign_id": campaignID, "query": strProp("Search text"), "limit": limit},
			[]string{"query"}, true, false),
		tool("propose_world_update",
			"Propose canon changes for DM approval.",
			map[string]any{
				"campaign_id": campaignID,
				"changes":     objProp("JSON array of change objects"),
				"reason":      strProp("Why these changes should be saved"),
			}, []string{"changes"}, false, false),
		tool("list_pending_updates",
			"List pending canon update proposals.",
			map[string]any{"campaign_id": campaignID}, nil, true, false),
		tool("commit_world_update",
			"Apply a pending update after DM approval.",
			map[string]any{"update_id": strProp("Pending update id")}, []string{"update_id"}, false, true),
		tool("reject_world_update",
			"Reject a pending update.",
			map[string]any{"update_id": strProp("Pending update id"), "reason": strProp("Optional rejection reason")},
			[]string{"update_id"}, false, true),
		tool("check_for_conflicts",
			"Detect possible contradictions in proposed changes.",
			map[string]any{"campaign_id": campaignID, "changes": objProp("Proposed changes JSON")},
			[]string{"changes"}, true, false),
		tool("start_session",
			"Open a new play session.",
			map[string]any{"campaign_id": campaignID, "title": strProp("Session title"), "notes": strProp("Optional session notes")},
			nil, false, false),
		tool("get_session",
			"Session workspace — notes, summary, events, entities modified, pending updates.",
			map[string]any{"session_id": strProp("Session id (defaults to active session)")}, nil, true, false),
		tool("end_session",
			"Close the active session; optional summary, notes, and changes propose a pending canon update.",
			map[string]any{
				"session_id": strProp("Session id (defaults to active session)"),
				"summary":    strProp("End-of-session summary for the DM"),
				"notes":      strProp("Optional session notes"),
				"changes":    objProp("Optional JSON array of canon changes to propose"),
				"reason":     strProp("Reason for proposed changes"),
			}, nil, false, false),
		tool("record_event",
			"Record a campaign event (via active session when set).",
			map[string]any{
				"campaign_id": campaignID,
				"session_id":  strProp("Session id (optional)"),
				"title":       strProp("Event title"),
				"summary":     strProp("Event summary"),
				"entity_ids":  objProp("JSON array of entity ids"),
			}, 			[]string{"title", "summary"}, false, false),
		tool("record_ruling",
			"Record a house ruling.",
			map[string]any{
				"campaign_id": campaignID,
				"question":    strProp("Rules question"),
				"answer":      strProp("Ruling answer"),
				"scope":       strProp("Scope (default campaign)"),
			}, []string{"question", "answer"}, false, false),
		tool("create_secret",
			"Propose a DM-only secret entity for approval.",
			map[string]any{
				"campaign_id": campaignID,
				"title":       strProp("Secret title"),
				"text":        strProp("Secret detail"),
				"reason":      strProp("Why this secret should be stored"),
			}, []string{"title"}, false, false),
		tool("import_campaign_markdown",
			"Parse Markdown headings into entity proposals for DM approval.",
			map[string]any{
				"campaign_id": campaignID,
				"markdown":    strProp("Markdown notes (## headings)"),
				"reason":      strProp("Import reason"),
			}, []string{"markdown"}, false, false),
		tool("set_campaign_role",
			"Set permissions role for campaign access (owner, dm, player).",
			map[string]any{
				"campaign_id": campaignID,
				"role":        strProp("owner, dm, or player"),
			}, []string{"role"}, false, false),
		tool("list_campaign_seats",
			"List DM and player seats with current controller (human or AI).",
			map[string]any{"campaign_id": campaignID}, nil, true, false),
		tool("get_seat",
			"Return one campaign seat by id.",
			map[string]any{"seat_id": strProp("Seat id")}, []string{"seat_id"}, true, false),
		tool("create_player_seat",
			"Create a player seat linked to a party actor entity.",
			map[string]any{
				"campaign_id":  campaignID,
				"actor_id":     strProp("Party actor entity id"),
				"display_name": strProp("Optional display name"),
			}, []string{"actor_id"}, false, false),
		tool("assign_seat_controller",
			"Assign human or AI controller to a seat (host/DM only).",
			map[string]any{
				"seat_id":              strProp("Seat id"),
				"controller":           strProp("human or ai"),
				"controller_user_id":   strProp("Required when controller is human"),
			}, []string{"seat_id", "controller"}, false, false),
		tool("handoff_seat",
			"Hand off seat control with audit log (host/DM only).",
			map[string]any{
				"seat_id":            strProp("Seat id"),
				"controller":         strProp("human or ai"),
				"controller_user_id": strProp("Required when controller is human"),
				"reason":             strProp("Why the handoff happened"),
				"session_id":         strProp("Optional session for audit linkage"),
			}, []string{"seat_id", "controller"}, false, false),
		tool("release_seat_to_ai",
			"Release a seat back to AI control (shorthand handoff).",
			map[string]any{
				"seat_id":    strProp("Seat id"),
				"reason":     strProp("Why the participant left"),
				"session_id": strProp("Optional session for audit linkage"),
			}, []string{"seat_id"}, false, false),
		tool("get_session_floor",
			"Who has the narrative floor in the open session (player-led default).",
			map[string]any{"session_id": strProp("Session id (defaults to open session)")}, nil, true, false),
		tool("search_rules_reference",
			"Search D&D 5e SRD rules text via Open5e (filtered by WORLDKEEP_SRD_VERSION). Returns snippets and rule_key for get_rules_section.",
			map[string]any{
				"query": strProp("Rules keyword or phrase"),
				"limit": limit,
			}, []string{"query"}, true, false),
		tool("get_rules_section",
			"Fetch full Open5e SRD rule section by key (from search_rules_reference rule_key).",
			map[string]any{"key": strProp("Open5e rule key, e.g. srd_monsters_grapple-rules")}, []string{"key"}, true, false),
	}
}
