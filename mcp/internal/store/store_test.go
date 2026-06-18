package store_test

import (
	"context"
	"encoding/json"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestMigrateAndSeedBlackport(t *testing.T) {
	t.Parallel()

	dir := t.TempDir()
	s, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = s.Close() })

	ctx := context.Background()
	if err := seed.Blackport(ctx, s); err != nil {
		t.Fatalf("seed: %v", err)
	}

	campaign, err := s.GetCampaign(ctx, seed.DemoCampaignID)
	if err != nil {
		t.Fatalf("get campaign: %v", err)
	}
	if campaign.Name != "Shadows of Blackport" {
		t.Fatalf("campaign name = %q", campaign.Name)
	}

	finn, err := s.GetEntity(ctx, "npc_finn")
	if err != nil {
		t.Fatalf("get finn: %v", err)
	}
	if finn.Type != "npc" {
		t.Fatalf("finn type = %q", finn.Type)
	}

	entities, facts, err := s.SearchWorld(ctx, seed.DemoCampaignID, "Crimson", 10)
	if err != nil {
		t.Fatalf("search: %v", err)
	}
	if len(entities) == 0 {
		t.Fatal("expected entity hits for Crimson")
	}
	if len(facts) == 0 {
		t.Fatal("expected fact hits for Crimson")
	}

	rulings, err := s.SearchRulings(ctx, seed.DemoCampaignID, "flanking", 5)
	if err != nil {
		t.Fatalf("search rulings: %v", err)
	}
	if len(rulings) != 1 {
		t.Fatalf("rulings = %d, want 1", len(rulings))
	}

	plots, err := s.ListActivePlots(ctx, seed.DemoCampaignID)
	if err != nil {
		t.Fatalf("list plots: %v", err)
	}
	if len(plots) != 1 {
		t.Fatalf("plots = %d, want 1", len(plots))
	}

	ev := store.Event{
		ID: "event_test", CampaignID: seed.DemoCampaignID,
		Title: "Test event", Summary: "Round-trip test",
		EntityIDs: json.RawMessage(`["npc_finn"]`),
	}
	if err := s.AddEvent(ctx, ev); err != nil {
		t.Fatalf("add event: %v", err)
	}
	recent, err := s.GetRecentEvents(ctx, seed.DemoCampaignID, 5)
	if err != nil {
		t.Fatalf("recent events: %v", err)
	}
	if len(recent) != 1 {
		t.Fatalf("recent = %d, want 1", len(recent))
	}
}
