package store_test

import (
	"context"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestHandoffSeatAuditAndActorContinuity(t *testing.T) {
	ctx := context.Background()
	st, err := store.Open(filepath.Join(t.TempDir(), "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	if err := seed.Blackport(ctx, st); err != nil {
		t.Fatalf("seed: %v", err)
	}

	before, err := st.GetEntity(ctx, "companion_lia")
	if err != nil {
		t.Fatalf("get entity: %v", err)
	}

	sess, err := st.StartSession(ctx, seed.DemoCampaignID, "Handoff test")
	if err != nil {
		t.Fatalf("start session: %v", err)
	}
	sid := sess.ID

	user := "user_alex"
	result, err := st.HandoffSeat(ctx, "seat_player_2", "human", &user, "Friend joins as Lia", &sid)
	if err != nil {
		t.Fatalf("handoff to human: %v", err)
	}
	if result.Seat.Controller != "human" || result.AuditEvent.Title != "seat_handoff" {
		t.Fatalf("unexpected handoff result: %+v", result)
	}

	after, err := st.GetEntity(ctx, "companion_lia")
	if err != nil {
		t.Fatalf("get entity after handoff: %v", err)
	}
	if after.Name != before.Name || string(after.Data) != string(before.Data) {
		t.Fatalf("actor profile changed on handoff")
	}

	floor, err := st.GetSessionFloor(ctx, sid)
	if err != nil || floor.FloorSeatID == nil || *floor.FloorSeatID != "seat_player_2" {
		t.Fatalf("expected floor on Lia seat, got %+v err=%v", floor, err)
	}

	released, err := st.ReleaseSeatToAI(ctx, "seat_player_2", "Player left the table", &sid)
	if err != nil {
		t.Fatalf("release to ai: %v", err)
	}
	if released.Seat.Controller != "ai" || released.Seat.ControllerUserID != nil {
		t.Fatalf("expected ai controller after release: %+v", released.Seat)
	}
}

func TestSessionFloorInit(t *testing.T) {
	ctx := context.Background()
	st, err := store.Open(filepath.Join(t.TempDir(), "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	if err := seed.Blackport(ctx, st); err != nil {
		t.Fatalf("seed: %v", err)
	}

	sess, err := st.StartSession(ctx, seed.DemoCampaignID, "Floor init")
	if err != nil {
		t.Fatalf("start: %v", err)
	}
	floor, err := st.GetSessionFloor(ctx, sess.ID)
	if err != nil {
		t.Fatalf("get floor: %v", err)
	}
	if floor.FloorSeatID == nil || *floor.FloorSeatID != "seat_player_1" {
		t.Fatalf("expected default floor on first player seat, got %+v", floor)
	}
}
