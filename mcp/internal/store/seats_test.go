package store_test

import (
	"context"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func TestCampaignSeatsLifecycle(t *testing.T) {
	ctx := context.Background()
	st, err := store.Open(filepath.Join(t.TempDir(), "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })

	campaignID := "campaign_seats_test"
	if err := st.CreateCampaign(ctx, store.Campaign{ID: campaignID, Name: "Seats test"}); err != nil {
		t.Fatalf("create campaign: %v", err)
	}
	actorID := campaignID + "_actor"
	if err := st.UpsertEntity(ctx, store.Entity{
		ID: actorID, CampaignID: campaignID, Type: "npc", Name: "Test PC",
	}); err != nil {
		t.Fatalf("upsert entity: %v", err)
	}

	dm, err := st.CreateSeat(ctx, store.CampaignSeat{
		ID:          "seat_dm_test",
		CampaignID:  campaignID,
		SeatType:    "dm",
		Controller:  "ai",
		DisplayName: "AI Dungeon Master",
		Status:      "active",
	})
	if err != nil {
		t.Fatalf("create dm seat: %v", err)
	}
	if dm.SeatType != "dm" || dm.Controller != "ai" {
		t.Fatalf("unexpected dm seat: %+v", dm)
	}

	player, err := st.CreatePlayerSeat(ctx, campaignID, actorID, "Test PC")
	if err != nil {
		t.Fatalf("create player seat: %v", err)
	}
	if player.ActorID == nil || *player.ActorID != actorID {
		t.Fatalf("expected actor on player seat")
	}

	list, err := st.ListCampaignSeats(ctx, campaignID)
	if err != nil || len(list) != 2 {
		t.Fatalf("list seats = %d err=%v", len(list), err)
	}

	user := "user_host_01"
	updated, err := st.AssignSeatController(ctx, player.ID, "human", &user)
	if err != nil {
		t.Fatalf("assign human: %v", err)
	}
	if updated.Controller != "human" || updated.ControllerUserID == nil || *updated.ControllerUserID != user {
		t.Fatalf("unexpected assigned seat: %+v", updated)
	}

	backToAI, err := st.AssignSeatController(ctx, player.ID, "ai", nil)
	if err != nil {
		t.Fatalf("assign ai: %v", err)
	}
	if backToAI.Controller != "ai" || backToAI.ControllerUserID != nil {
		t.Fatalf("expected ai controller with cleared user")
	}
}
