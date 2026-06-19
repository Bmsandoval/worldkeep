package main

import (
	"context"
	"log"

	"github.com/Bmsandoval/worldkeep/mcp/internal/config"
	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"

	"github.com/joho/godotenv"
)

func main() {
	_ = godotenv.Load("local.env")
	_ = godotenv.Load("../local.env")

	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("config: %v", err)
	}

	st, err := store.Open(cfg.DBPath(seed.DemoCampaignID))
	if err != nil {
		log.Fatalf("open store: %v", err)
	}
	defer st.Close()

	ctx := context.Background()
	if err := seed.EnsureDemoSeats(ctx, st, seed.DemoCampaignID); err != nil {
		log.Fatalf("backfill seats: %v", err)
	}

	seats, err := st.ListCampaignSeats(ctx, seed.DemoCampaignID)
	if err != nil {
		log.Fatalf("list seats: %v", err)
	}
	log.Printf("campaign %q has %d seat(s)", seed.DemoCampaignID, len(seats))
}
