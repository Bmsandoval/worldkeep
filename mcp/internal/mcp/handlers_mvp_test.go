package mcp

import (
	"encoding/json"
	"strings"
	"testing"
)

func TestPrepareSessionBrief(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "prepare_session_brief", map[string]any{})
	for _, want := range []string{"Shadows of Blackport", "Missing Prince", "pending_update_count"} {
		if !strings.Contains(text, want) {
			t.Fatalf("expected %q in session brief", want)
		}
	}
}

func TestPartyScopeHidesSecretsInSceneContext(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "compile_scene_context", map[string]any{
		"prompt": "Prepare session in Blackport about the prince.",
		"scope":  "party",
	})
	if strings.Contains(text, "Prince still alive") || strings.Contains(text, "imprisoned beneath the docks") {
		t.Fatal("party scope leaked dm secret into scene context")
	}
}

func TestDMSScopeShowsSecretsInSearch(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "search_world", map[string]any{
		"query": "imprisoned beneath",
		"scope": "dm",
	})
	if !strings.Contains(text, "imprisoned") {
		t.Fatal("dm scope search should include prince secret fact")
	}
}

func TestGetSessionWorkspace(t *testing.T) {
	srv := testServer(t)
	startText := callTool(t, srv, "start_session", map[string]any{
		"title": "Workspace test",
		"notes": "Opening scene at the tavern.",
	})
	var sess struct {
		ID string `json:"id"`
	}
	if err := json.Unmarshal([]byte(startText), &sess); err != nil {
		t.Fatal(err)
	}

	commitSpyFact(t, srv)

	wsText := callTool(t, srv, "get_session", map[string]any{"session_id": sess.ID})
	if !strings.Contains(wsText, "Workspace test") || !strings.Contains(wsText, "Opening scene") {
		t.Fatal("expected session workspace details")
	}
	if !strings.Contains(wsText, "npc_finn") {
		t.Fatal("expected modified entity tracking for Finn")
	}
}

func TestImportCampaignMarkdownProposesEntities(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "import_campaign_markdown", map[string]any{
		"markdown": "## NPC: Test Merchant\nSells rope.",
	})
	if !strings.Contains(text, "pending_update") {
		t.Fatal("expected pending update from markdown import")
	}
}

func TestPlayerRoleCannotUseDMScope(t *testing.T) {
	srv := testServer(t)
	srv.Role = "player"
	resp, ok := srv.Dispatch(rpcRequest{
		JSONRPC: "2.0",
		ID:      json.RawMessage(`1`),
		Method:  "tools/call",
		Params:  json.RawMessage(`{"name":"prepare_session_brief","arguments":{"scope":"dm"}}`),
	})
	if !ok || resp.Error == nil {
		t.Fatal("expected error for player dm scope")
	}
}

func commitSpyFact(t *testing.T, srv *Server) {
	t.Helper()
	propose := callTool(t, srv, "propose_world_update", map[string]any{
		"changes": []any{map[string]any{
			"op": "add_fact",
			"fact": map[string]any{
				"entity_id":  "npc_finn",
				"text":       "Finn agreed to spy.",
				"visibility": "party_known",
				"confidence": "high",
			},
		}},
		"reason": "test",
	})
	var payload struct {
		PendingUpdate struct {
			ID string `json:"id"`
		} `json:"pending_update"`
	}
	if err := json.Unmarshal([]byte(propose), &payload); err != nil {
		t.Fatal(err)
	}
	callTool(t, srv, "commit_world_update", map[string]any{"update_id": payload.PendingUpdate.ID})
}
