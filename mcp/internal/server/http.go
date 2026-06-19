package server

import (
	"fmt"
	"net/http"
	"os"

	"github.com/Bmsandoval/worldkeep/mcp/internal/api"
	"github.com/Bmsandoval/worldkeep/mcp/internal/config"
	"github.com/Bmsandoval/worldkeep/mcp/internal/mcp"
	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

// Options configures the unified HTTP server (MCP + REST + shared store).
type Options struct {
	Addr       string
	CampaignID string
	Role       string
	Auth       api.AuthConfig
}

// DefaultOptions loads campaign and auth from environment.
func DefaultOptions(cfg config.Config) Options {
	addr := os.Getenv("WORLDKEEP_HTTP_ADDR")
	if addr == "" {
		addr = os.Getenv("WORLDKEEP_MCP_ADDR")
	}
	if addr == "" {
		addr = ":8788"
	}
	campaignID := os.Getenv("WORLDKEEP_CAMPAIGN_ID")
	if campaignID == "" {
		campaignID = seed.DemoCampaignID
	}
	auth := api.AuthFromEnv()
	role := auth.RoleOrDefault(cfg.Role)
	return Options{
		Addr:       addr,
		CampaignID: campaignID,
		Role:       role,
		Auth:       auth,
	}
}

// OpenStore opens the campaign SQLite database.
func OpenStore(cfg config.Config, campaignID string) (*store.Store, error) {
	return store.Open(cfg.DBPath(campaignID))
}

// NewHandler returns an http.Handler serving MCP, REST API, and health checks on one mux.
func NewHandler(st *store.Store, opts Options) http.Handler {
	mcpSrv := &mcp.Server{
		Store:      st,
		CampaignID: opts.CampaignID,
		Role:       opts.Role,
	}
	apiSvc := &api.Service{
		Store:      st,
		CampaignID: opts.CampaignID,
		Role:       opts.Role,
	}
	apiSrv := &api.Server{Svc: apiSvc, Auth: opts.Auth}

	mux := http.NewServeMux()
	mcpSrv.MountHTTP(mux)
	apiSrv.Mount(mux)
	return api.CombinedHandler(mux, opts.Auth)
}

// Listen starts the unified HTTP server.
func Listen(st *store.Store, opts Options) error {
	fmt.Printf("worldkeep HTTP listening on %s (campaign=%s role=%s mcp=/mcp api=/api/v1 auth=%t)\n",
		opts.Addr, opts.CampaignID, opts.Role, opts.Auth.Token != "")
	return http.ListenAndServe(opts.Addr, NewHandler(st, opts))
}
