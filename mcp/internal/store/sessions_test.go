package store_test

import (
	"context"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestSessionLifecycle(t *testing.T) {
	dir := t.TempDir()
	s, _ := store.Open(filepath.Join(dir, "test.sqlite"))
	ctx := context.Background()
	_ = seed.Blackport(ctx, s)

	sess, err := s.StartSession(ctx, seed.DemoCampaignID, "Session 3")
	if err != nil {
		t.Fatalf("start: %v", err)
	}
	if sess.Status != "open" {
		t.Fatalf("status = %q", sess.Status)
	}
	open, err := s.GetOpenSession(ctx, seed.DemoCampaignID)
	if err != nil || open.ID != sess.ID {
		t.Fatalf("open session: %v", err)
	}
	closed, err := s.EndSession(ctx, sess.ID)
	if err != nil || closed.Status != "closed" || closed.EndedAt == nil {
		t.Fatalf("end: %+v err=%v", closed, err)
	}
}
