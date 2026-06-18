package store_test

import (
	"context"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestCheckForConflictsFinnEye(t *testing.T) {
	dir := t.TempDir()
	s, _ := store.Open(filepath.Join(dir, "test.sqlite"))
	ctx := context.Background()
	_ = seed.Blackport(ctx, s)
	finnID := "npc_finn"
	warnings, err := s.CheckForConflicts(ctx, seed.DemoCampaignID, []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			EntityID: &finnID, Text: "Finn has both eyes.",
			Visibility: "party_known", Confidence: "high",
		},
	}})
	if err != nil {
		t.Fatalf("check: %v", err)
	}
	if len(warnings) == 0 {
		t.Fatal("expected contradiction warning")
	}
}
