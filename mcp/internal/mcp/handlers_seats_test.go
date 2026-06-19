package mcp

import (
	"context"
	"encoding/json"
	"path/filepath"
	"strings"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestCampaignSeatsMCP(t *testing.T) {
	dir := t.TempDir()
	st, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	ctx := context.Background()
	if err := seed.Blackport(ctx, st); err != nil {
		t.Fatalf("seed: %v", err)
	}

	srv := &Server{Store: st, CampaignID: seed.DemoCampaignID, Role: "dm"}

	listText := callTool(t, srv, "list_campaign_seats", map[string]any{})
	if !strings.Contains(listText, "seat_dm") || !strings.Contains(listText, "seat_player_1") {
		t.Fatalf("expected demo seats: %s", listText)
	}
	if !strings.Contains(listText, "AI Dungeon Master") || !strings.Contains(listText, "companion_kael") {
		t.Fatalf("expected AI DM and companions in seats: %s", listText)
	}

	getText := callTool(t, srv, "get_seat", map[string]any{"seat_id": "seat_player_2"})
	if !strings.Contains(getText, "Lia") || !strings.Contains(getText, "companion_lia") {
		t.Fatalf("expected Lia seat: %s", getText)
	}

	assignText := callTool(t, srv, "assign_seat_controller", map[string]any{
		"seat_id":             "seat_player_2",
		"controller":          "human",
		"controller_user_id":  "user_alex",
	})
	if !strings.Contains(assignText, "human") || !strings.Contains(assignText, "user_alex") {
		t.Fatalf("expected human assignment: %s", assignText)
	}

	releaseText := callTool(t, srv, "assign_seat_controller", map[string]any{
		"seat_id":    "seat_player_2",
		"controller": "ai",
	})
	if !strings.Contains(releaseText, `"controller":"ai"`) && !strings.Contains(releaseText, `"controller": "ai"`) {
		t.Fatalf("expected ai controller restored: %s", releaseText)
	}

	playerSrv := &Server{Store: st, CampaignID: seed.DemoCampaignID, Role: "player"}
	raw, _ := json.Marshal(map[string]any{
		"seat_id":            "seat_player_1",
		"controller":         "human",
		"controller_user_id": "user_blocked",
	})
	toolArgs, _ := json.Marshal(map[string]any{"name": "assign_seat_controller", "arguments": json.RawMessage(raw)})
	resp, ok := playerSrv.Dispatch(rpcRequest{
		JSONRPC: "2.0",
		ID:      json.RawMessage(`2`),
		Method:  "tools/call",
		Params:  toolArgs,
	})
	if !ok || resp.Error == nil {
		t.Fatalf("expected permission error, got %+v", resp)
	}
	if !strings.Contains(resp.Error.Message, "player role cannot manage seats") {
		t.Fatalf("unexpected error: %s", resp.Error.Message)
	}
}

func TestCreatePlayerSeatMCP(t *testing.T) {
	dir := t.TempDir()
	st, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	ctx := context.Background()
	if err := seed.Blackport(ctx, st); err != nil {
		t.Fatalf("seed: %v", err)
	}

	srv := &Server{Store: st, CampaignID: seed.DemoCampaignID, Role: "owner"}
	text := callTool(t, srv, "create_player_seat", map[string]any{
		"actor_id": "npc_finn",
		"display_name": "Finn (guest slot)",
	})
	if !strings.Contains(text, "Finn (guest slot)") || !strings.Contains(text, "npc_finn") {
		t.Fatalf("expected new player seat: %s", text)
	}
}
