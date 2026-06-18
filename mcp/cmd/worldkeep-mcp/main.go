package main

import (
	"log"
	"os"

	"github.com/Bmsandoval/worldkeep/mcp/internal/config"
	"github.com/Bmsandoval/worldkeep/mcp/internal/mcp"
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

	campaignID := os.Getenv("WORLDKEEP_CAMPAIGN_ID")
	if campaignID == "" {
		campaignID = seed.DemoCampaignID
	}

	dbPath := cfg.DBPath(campaignID)
	st, err := store.Open(dbPath)
	if err != nil {
		log.Fatalf("open store %s: %v", dbPath, err)
	}
	defer st.Close()

	srv := &mcp.Server{Store: st, CampaignID: campaignID}
	if err := srv.RunStdioOS(); err != nil {
		log.Fatalf("stdio: %v", err)
	}
}
