package mcp

import (
	"context"
	"encoding/json"
	"strings"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestProposeCommitWorldUpdateMCP(t *testing.T) {
	srv := testServer(t)
	finnID := "npc_finn"
	changes := []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			EntityID: &finnID, Text: "Finn agreed to spy on the Crimson Guild.",
			Visibility: "party_known", Confidence: "high",
		},
	}}
	text := callTool(t, srv, "propose_world_update", map[string]any{
		"changes": changes,
		"reason":  "Finn recruited as informant",
	})
	if !strings.Contains(text, "update_") {
		t.Fatalf("expected pending update id in %s", text)
	}
	var parsed struct {
		PendingUpdate struct {
			ID string `json:"id"`
		} `json:"pending_update"`
	}
	if err := json.Unmarshal([]byte(text), &parsed); err != nil || parsed.PendingUpdate.ID == "" {
		t.Fatalf("parse pending update: %v text=%s", err, text)
	}
	commitText := callTool(t, srv, "commit_world_update", map[string]any{"update_id": parsed.PendingUpdate.ID})
	if !strings.Contains(commitText, "committed") {
		t.Fatalf("expected committed status: %s", commitText)
	}
}

func TestCheckForConflictsFinnEyeMCP(t *testing.T) {
	srv := testServer(t)
	finnID := "npc_finn"
	changes := []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			EntityID: &finnID, Text: "Finn has both eyes.",
			Visibility: "party_known", Confidence: "high",
		},
	}}
	text := callTool(t, srv, "check_for_conflicts", map[string]any{"changes": changes})
	if !strings.Contains(text, "contradiction") && !strings.Contains(text, "left eye") {
		t.Fatalf("expected contradiction warning: %s", text)
	}
}

func TestListPendingUpdatesIncludesWarnings(t *testing.T) {
	srv := testServer(t)
	finnID := "npc_finn"
	changes := []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			EntityID: &finnID, Text: "Finn has both eyes.",
			Visibility: "party_known", Confidence: "high",
		},
	}}
	_ = callTool(t, srv, "propose_world_update", map[string]any{"changes": changes, "reason": "test"})
	listText := callTool(t, srv, "list_pending_updates", map[string]any{})
	if !strings.Contains(listText, "warnings") {
		t.Fatalf("expected warnings in list output: %s", listText)
	}
}

func TestRejectWorldUpdateMCP(t *testing.T) {
	srv := testServer(t)
	finnID := "npc_finn"
	changes := []store.WorldChange{{
		Op:   "add_fact",
		Fact: &store.Fact{EntityID: &finnID, Text: "temporary", Visibility: "party_known", Confidence: "low"},
	}}
	pending, err := srv.Store.ProposeWorldUpdate(context.Background(), seed.DemoCampaignID, changes, "test")
	if err != nil {
		t.Fatalf("propose: %v", err)
	}
	text := callTool(t, srv, "reject_world_update", map[string]any{
		"update_id": pending.ID,
		"reason":    "test",
	})
	if !strings.Contains(text, "rejected") {
		t.Fatalf("expected rejected: %s", text)
	}
}
