package mcp

import (
	"context"
	"encoding/json"
	"path/filepath"
	"strings"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func testServer(t *testing.T) *Server {
	t.Helper()
	dir := t.TempDir()
	st, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	if err := seed.Blackport(context.Background(), st); err != nil {
		t.Fatalf("seed: %v", err)
	}
	return &Server{Store: st, CampaignID: seed.DemoCampaignID}
}

func callTool(t *testing.T, srv *Server, name string, args any) string {
	t.Helper()
	raw, _ := json.Marshal(args)
	toolArgs, _ := json.Marshal(map[string]any{"name": name, "arguments": json.RawMessage(raw)})
	resp, ok := srv.Dispatch(rpcRequest{
		JSONRPC: "2.0",
		ID:      json.RawMessage(`1`),
		Method:  "tools/call",
		Params:  toolArgs,
	})
	if !ok || resp.Error != nil {
		t.Fatalf("%s: %+v", name, resp)
	}
	result := resp.Result.(map[string]any)
	items, ok := result["content"].([]map[string]any)
	if !ok {
		// JSON decode via round-trip for generic slice typing
		b, _ := json.Marshal(result["content"])
		var generic []map[string]any
		_ = json.Unmarshal(b, &generic)
		items = generic
	}
	return items[0]["text"].(string)
}

func TestGetCampaignOverview(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "get_campaign_overview", map[string]any{})
	if !strings.Contains(text, "Shadows of Blackport") {
		t.Fatal("expected campaign name in overview")
	}
}

func TestGetEntityFinn(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "get_entity", map[string]any{"entity_id": "npc_finn"})
	if !strings.Contains(text, "Finn") {
		t.Fatal("expected Finn entity")
	}
}

func TestSearchWorldCrimson(t *testing.T) {
	srv := testServer(t)
	text := callTool(t, srv, "search_world", map[string]any{"query": "Crimson"})
	if !strings.Contains(text, "Crimson Guild") {
		t.Fatal("expected Crimson Guild in search results")
	}
}
