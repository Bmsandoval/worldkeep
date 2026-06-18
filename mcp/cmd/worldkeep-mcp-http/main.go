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
	addr := os.Getenv("WORLDKEEP_MCP_ADDR")
	if addr == "" {
		addr = ":8788"
	}

	campaignID := os.Getenv("WORLDKEEP_CAMPAIGN_ID")
	if campaignID == "" {
		campaignID = seed.DemoCampaignID
	}

	st, err := store.Open(cfg.DBPath(campaignID))
	if err != nil {
		log.Fatalf("open store: %v", err)
	}
	defer st.Close()

	srv := &mcp.Server{Store: st, CampaignID: campaignID}
	if err := mcp.ListenHTTP(addr, srv); err != nil {
		log.Fatalf("http: %v", err)
	}
}
