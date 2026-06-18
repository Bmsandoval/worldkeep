package store_test

import (
	"context"
	"encoding/json"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestProposeCommitRejectUpdate(t *testing.T) {
	dir := t.TempDir()
	s, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = stClose(s) })
	ctx := context.Background()
	if err := seed.Blackport(ctx, s); err != nil {
		t.Fatalf("seed: %v", err)
	}

	finnID := "npc_finn"
	changes := []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			CampaignID: seed.DemoCampaignID, EntityID: &finnID,
			Text: "Finn agreed to spy on the Crimson Guild.", Visibility: "party_known", Confidence: "high",
		},
	}}
	pending, err := s.ProposeWorldUpdate(ctx, seed.DemoCampaignID, changes, "Finn recruited as informant")
	if err != nil {
		t.Fatalf("propose: %v", err)
	}
	list, err := s.ListPendingUpdates(ctx, seed.DemoCampaignID)
	if err != nil || len(list) != 1 {
		t.Fatalf("list pending: %v len=%d", err, len(list))
	}
	if _, err := s.CommitWorldUpdate(ctx, pending.ID); err != nil {
		t.Fatalf("commit: %v", err)
	}
	entities, facts, err := s.SearchWorld(ctx, seed.DemoCampaignID, "spy", 5)
	if err != nil {
		t.Fatalf("search: %v", err)
	}
	if len(facts) == 0 {
		t.Fatalf("expected new fact, entities=%d", len(entities))
	}

	pending2, err := s.ProposeWorldUpdate(ctx, seed.DemoCampaignID, changes, "duplicate")
	if err != nil {
		t.Fatalf("propose2: %v", err)
	}
	if err := s.RejectWorldUpdate(ctx, pending2.ID, "not now"); err != nil {
		t.Fatalf("reject: %v", err)
	}
	u, _ := s.GetPendingUpdate(ctx, pending2.ID)
	if u.Status != "rejected" {
		t.Fatalf("status = %q", u.Status)
	}
}

func TestPatchEntity(t *testing.T) {
	dir := t.TempDir()
	s, _ := store.Open(filepath.Join(dir, "test.sqlite"))
	ctx := context.Background()
	_ = seed.Blackport(ctx, s)
	patch, _ := json.Marshal(map[string]any{"summary": "Trusted informant."})
	if err := s.PatchEntity(ctx, "npc_finn", patch); err != nil {
		t.Fatalf("patch: %v", err)
	}
	finn, _ := s.GetEntity(ctx, "npc_finn")
	if finn.Summary != "Trusted informant." {
		t.Fatalf("summary = %q", finn.Summary)
	}
}

func stClose(s *store.Store) error { return s.Close() }
