package seed

import (
	"context"
	"encoding/json"
	"fmt"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

// EnsureDemoSeats upserts companion actors and default AI-controlled seats for a demo campaign.
func EnsureDemoSeats(ctx context.Context, s *store.Store, campaignID string) error {
	companions := []store.Entity{
		{
			ID: "companion_kael", CampaignID: campaignID, Type: "npc",
			Name: "Kael", Summary: "Party scout — quick wit, quicker blades.",
			Data: json.RawMessage(`{"role":"companion","class":"rogue","tags":["party","companion"]}`),
		},
		{
			ID: "companion_lia", CampaignID: campaignID, Type: "npc",
			Name: "Lia", Summary: "Scholarly cleric keeping the party grounded.",
			Data: json.RawMessage(`{"role":"companion","class":"cleric","tags":["party","companion"]}`),
		},
	}
	for _, e := range companions {
		if err := s.UpsertEntity(ctx, e); err != nil {
			return fmt.Errorf("seed companion %s: %w", e.ID, err)
		}
	}

	count, err := s.CountCampaignSeats(ctx, campaignID)
	if err != nil {
		return err
	}
	if count > 0 {
		return nil
	}

	if _, err := s.CreateSeat(ctx, store.CampaignSeat{
		ID:          "seat_dm",
		CampaignID:  campaignID,
		SeatType:    "dm",
		Controller:  "ai",
		DisplayName: "AI Dungeon Master",
		Status:      "active",
	}); err != nil {
		return fmt.Errorf("seed dm seat: %w", err)
	}

	kael := "companion_kael"
	if _, err := s.CreateSeat(ctx, store.CampaignSeat{
		ID:          "seat_player_1",
		CampaignID:  campaignID,
		SeatType:    "player",
		Controller:  "ai",
		ActorID:     &kael,
		DisplayName: "Kael",
		Status:      "active",
	}); err != nil {
		return fmt.Errorf("seed player seat 1: %w", err)
	}

	lia := "companion_lia"
	if _, err := s.CreateSeat(ctx, store.CampaignSeat{
		ID:          "seat_player_2",
		CampaignID:  campaignID,
		SeatType:    "player",
		Controller:  "ai",
		ActorID:     &lia,
		DisplayName: "Lia",
		Status:      "active",
	}); err != nil {
		return fmt.Errorf("seed player seat 2: %w", err)
	}

	return nil
}
