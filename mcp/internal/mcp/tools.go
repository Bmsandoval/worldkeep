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

func toolDefs() []map[string]any {
	campaignID := strProp("Campaign id (default: campaign_001)")
	limit := intProp("Max results (default 10)")

	return []map[string]any{
		tool("get_campaign_dashboard",
			"Campaign health snapshot: open session, active plots, recent events, pending approvals, continuity warnings.",
			map[string]any{"campaign_id": campaignID, "event_limit": limit}, nil, true, false),
		tool("get_campaign_overview",
			"Campaign summary, active plots, and major actors.",
			map[string]any{"campaign_id": campaignID}, nil, true, false),
		tool("get_entity",
			"Return one full entity by id.",
			map[string]any{"entity_id": strProp("Entity id")}, []string{"entity_id"}, true, false),
		tool("search_world",
			"Search entities and facts by keyword.",
			map[string]any{"campaign_id": campaignID, "query": strProp("Search text"), "limit": limit},
			[]string{"query"}, true, false),
		tool("compile_scene_context",
			"Primary context endpoint — relevant actors, facts, events, plots for a scene prompt.",
			map[string]any{
				"campaign_id": campaignID,
				"prompt":      strProp("Natural-language scene prompt"),
				"limit":       limit,
			}, []string{"prompt"}, true, false),
		tool("get_recent_events",
			"Recent campaign events.",
			map[string]any{"campaign_id": campaignID, "limit": limit}, nil, true, false),
		tool("get_active_plots",
			"Unresolved active plots.",
			map[string]any{"campaign_id": campaignID}, nil, true, false),
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
			map[string]any{"campaign_id": campaignID, "title": strProp("Session title")},
			nil, false, false),
		tool("end_session",
			"Close the active session; optional summary and changes propose a pending canon update.",
			map[string]any{
				"session_id": strProp("Session id (defaults to active session)"),
				"summary":    strProp("End-of-session summary for the DM"),
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
			}, []string{"title", "summary"}, false, false),
		tool("record_ruling",
			"Record a house ruling.",
			map[string]any{
				"campaign_id": campaignID,
				"question":    strProp("Rules question"),
				"answer":      strProp("Ruling answer"),
				"scope":       strProp("Scope (default campaign)"),
			}, []string{"question", "answer"}, false, false),
	}
}
