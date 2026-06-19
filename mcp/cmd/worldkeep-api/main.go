package main

import (
	"log"

	"github.com/Bmsandoval/worldkeep/mcp/internal/config"
	"github.com/Bmsandoval/worldkeep/mcp/internal/server"

	"github.com/joho/godotenv"
)

func main() {
	_ = godotenv.Load("local.env")
	_ = godotenv.Load("../local.env")

	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("config: %v", err)
	}
	opts := server.DefaultOptions(cfg)

	st, err := server.OpenStore(cfg, opts.CampaignID)
	if err != nil {
		log.Fatalf("open store: %v", err)
	}
	defer st.Close()

	log.Println("worldkeep-api: use worldkeep-serve (unified MCP + REST on WORLDKEEP_HTTP_ADDR)")
	if err := server.Listen(st, opts); err != nil {
		log.Fatalf("http: %v", err)
	}
}
