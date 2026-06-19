package mcp

import (
	"context"
	"path/filepath"
	"strings"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestHandoffPlaytestScenario(t *testing.T) {
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

	listText := callTool(t, srv, "list_campaign_seats", map[string]any{})
	if !strings.Contains(listText, `"controller":"ai"`) && !strings.Contains(listText, `"controller": "ai"`) {
		t.Fatalf("expected AI-controlled demo seats: %s", listText)
	}

	startText := callTool(t, srv, "start_session", map[string]any{"title": "Solo with AI table"})
	if !strings.Contains(startText, "session_") {
		t.Fatalf("expected session: %s", startText)
	}

	floorText := callTool(t, srv, "get_session_floor", map[string]any{})
	if !strings.Contains(floorText, "seat_player_1") {
		t.Fatalf("expected player-led floor default: %s", floorText)
	}

	liaBefore := callTool(t, srv, "get_entity", map[string]any{"entity_id": "companion_lia"})

	handoffText := callTool(t, srv, "handoff_seat", map[string]any{
		"seat_id":            "seat_player_2",
		"controller":         "human",
		"controller_user_id": "user_lia",
		"reason":             "Alex joins as Lia",
	})
	if !strings.Contains(handoffText, "user_lia") || !strings.Contains(handoffText, "seat_handoff") {
		t.Fatalf("expected audited human handoff: %s", handoffText)
	}

	liaAfter := callTool(t, srv, "get_entity", map[string]any{"entity_id": "companion_lia"})
	if liaBefore != liaAfter {
		t.Fatal("companion entity changed during seat handoff")
	}

	dmHandoff := callTool(t, srv, "handoff_seat", map[string]any{
		"seat_id":            "seat_dm",
		"controller":         "human",
		"controller_user_id": "user_dm",
		"reason":             "Human DM takeover",
	})
	if !strings.Contains(dmHandoff, "user_dm") {
		t.Fatalf("expected DM handoff: %s", dmHandoff)
	}

	releaseText := callTool(t, srv, "release_seat_to_ai", map[string]any{
		"seat_id": "seat_player_2",
		"reason":  "Alex had to leave",
	})
	if !strings.Contains(releaseText, `"controller":"ai"`) && !strings.Contains(releaseText, `"controller": "ai"`) {
		t.Fatalf("expected Lia returned to AI: %s", releaseText)
	}

	events := callTool(t, srv, "get_recent_events", map[string]any{"limit": 10})
	if !strings.Contains(events, "seat_handoff") {
		t.Fatalf("expected handoff audit events: %s", events)
	}
}
