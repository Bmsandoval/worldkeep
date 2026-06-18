package mcp

import (
	"strings"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestEndSessionWithProposal(t *testing.T) {
	srv := testServer(t)
	_ = callTool(t, srv, "start_session", map[string]any{"title": "Finn spy session"})
	finnID := "npc_finn"
	changes := []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			EntityID: &finnID, Text: "Finn agreed to spy on the Crimson Guild.",
			Visibility: "party_known", Confidence: "high",
		},
	}}
	endText := callTool(t, srv, "end_session", map[string]any{
		"summary": "Party recruited Finn as an informant.",
		"changes": changes,
		"reason":  "Save session canon",
	})
	if !strings.Contains(endText, "pending_update") || !strings.Contains(endText, "closed") {
		t.Fatalf("expected closed session + pending update: %s", endText)
	}
}

func TestRecordEventAndRulingMCP(t *testing.T) {
	srv := testServer(t)
	_ = callTool(t, srv, "start_session", map[string]any{"title": "Rec test"})
	evText := callTool(t, srv, "record_event", map[string]any{
		"title":      "Finn recruited",
		"summary":    "Finn agreed to spy on the Crimson Guild.",
		"entity_ids": []string{"npc_finn", "faction_crimson_guild"},
	})
	if !strings.Contains(evText, "event_") {
		t.Fatalf("expected event: %s", evText)
	}
	ruleText := callTool(t, srv, "record_ruling", map[string]any{
		"question": "Can Finn lie to the Guild?",
		"answer":   "Yes, but he risks his tavern if caught.",
	})
	if !strings.Contains(ruleText, "ruling_") {
		t.Fatalf("expected ruling: %s", ruleText)
	}
	recent := callTool(t, srv, "get_recent_events", map[string]any{"limit": 5})
	if !strings.Contains(recent, "Finn recruited") {
		t.Fatalf("expected recent event: %s", recent)
	}
}
