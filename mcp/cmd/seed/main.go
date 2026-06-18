package main

import (
	"context"
	"log"
	"os"
	"path/filepath"

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
	if err := os.MkdirAll(cfg.DataDir, 0o755); err != nil {
		log.Fatalf("mkdir data: %v", err)
	}

	dbPath := cfg.DBPath(seed.DemoCampaignID)
	if _, err := os.Stat(dbPath); err == nil {
		log.Fatalf("campaign database already exists: %s (delete it to re-seed)", dbPath)
	}

	s, err := store.Open(dbPath)
	if err != nil {
		log.Fatalf("open store: %v", err)
	}
	defer s.Close()

	ctx := context.Background()
	if err := seed.Blackport(ctx, s); err != nil {
		log.Fatalf("seed: %v", err)
	}

	abs, _ := filepath.Abs(dbPath)
	log.Printf("seeded demo campaign %q at %s", seed.DemoCampaignID, abs)
}
