package seed

import (
	"context"
	"encoding/json"
	"fmt"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

const DemoCampaignID = "campaign_001"

// Blackport seeds the POC demo scenario from docs/poc.md §13.
func Blackport(ctx context.Context, s *store.Store) error {
	if err := s.CreateCampaign(ctx, store.Campaign{
		ID:     DemoCampaignID,
		Name:   "Shadows of Blackport",
		System: "D&D 3.5e",
	}); err != nil {
		return err
	}

	entities := []store.Entity{
		{
			ID: "location_blackport", CampaignID: DemoCampaignID, Type: "location",
			Name: "Blackport", Summary: "A fog-heavy port city ruled by Duke Harland.",
			Data: json.RawMessage(`{"type":"city","status":"tense","tags":["port","political","urban"]}`),
		},
		{
			ID: "npc_finn", CampaignID: DemoCampaignID, Type: "npc",
			Name: "Finn", Summary: "One-eyed bartender at the Salt Lantern.",
			Data: json.RawMessage(`{
				"location_id":"location_blackport",
				"status":"alive",
				"attitude_to_party":10,
				"goals":["Keep his tavern safe","Avoid angering the Crimson Guild"],
				"beliefs":[{"text":"The Crimson Guild controls the docks.","confidence":"high"}],
				"tags":["bartender","informant"]
			}`),
		},
		{
			ID: "faction_crimson_guild", CampaignID: DemoCampaignID, Type: "faction",
			Name: "Crimson Guild", Summary: "A smuggling syndicate operating through Blackport.",
			Data: json.RawMessage(`{
				"power":65,
				"attitude_to_party":-10,
				"goals":["Control dockside trade","Keep nobles dependent on smuggled goods"]
			}`),
		},
		{
			ID: "plot_missing_prince", CampaignID: DemoCampaignID, Type: "plot",
			Name: "The Missing Prince", Summary: "The prince disappeared after investigating Crimson Guild activity.",
			Data: json.RawMessage(`{
				"status":"active",
				"urgency":"medium",
				"involved_entities":["faction_crimson_guild","location_blackport"]
			}`),
		},
	}
	for _, e := range entities {
		if err := s.UpsertEntity(ctx, e); err != nil {
			return fmt.Errorf("seed entity %s: %w", e.ID, err)
		}
	}

	finnID := "npc_finn"
	if err := s.AddFact(ctx, store.Fact{
		ID: "fact_finn_fears_guild", CampaignID: DemoCampaignID, EntityID: &finnID,
		Text: "Finn fears the Crimson Guild.", Visibility: "party_known", Confidence: "high",
	}); err != nil {
		return err
	}
	if err := s.AddFact(ctx, store.Fact{
		ID: "fact_finn_eye", CampaignID: DemoCampaignID, EntityID: &finnID,
		Text: "Finn lost his left eye in a dockside knife fight.", Visibility: "party_known", Confidence: "high",
	}); err != nil {
		return err
	}

	if err := s.AddRuling(ctx, store.Ruling{
		ID: "ruling_001", CampaignID: DemoCampaignID,
		Question: "Does flanking grant +3 instead of +2?",
		Answer:   "Yes. In this campaign, flanking grants +3.",
		Scope:    "campaign", System: "D&D 3.5e",
	}); err != nil {
		return err
	}

	return nil
}
