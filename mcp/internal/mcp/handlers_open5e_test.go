package mcp

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"path/filepath"
	"strings"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/open5e"
	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func testServerWithOpen5e(t *testing.T, handler http.HandlerFunc) *Server {
	t.Helper()
	srv := httptest.NewServer(handler)
	t.Cleanup(srv.Close)

	dir := t.TempDir()
	st, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	if err := seed.Blackport(context.Background(), st); err != nil {
		t.Fatalf("seed: %v", err)
	}
	return &Server{
		Store:      st,
		CampaignID: seed.DemoCampaignID,
		Open5e: &open5e.Client{
			BaseURL:    srv.URL + "/v2",
			HTTP:       srv.Client(),
			SRDDocKeys: []string{"srd-2014"},
		},
	}
}

func TestSearchRulesReferenceTool(t *testing.T) {
	srv := testServerWithOpen5e(t, func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte(`{
			"count": 1,
			"results": [{
				"document": {"key": "srd-2014", "name": "SRD 5.1"},
				"object_pk": "srd_monsters_grapple-rules",
				"object_name": "Grapple Rules for Monsters",
				"object_model": "Rule",
				"text": "Grapple rules.",
				"match_score": 1.0
			}]
		}`))
	})
	text := callTool(t, srv, "search_rules_reference", map[string]any{"query": "grapple"})
	if !strings.Contains(text, "srd_monsters_grapple-rules") || !strings.Contains(text, "Grapple Rules") {
		t.Fatalf("unexpected search output: %s", text)
	}
}

func TestGetRulesSectionTool(t *testing.T) {
	srv := testServerWithOpen5e(t, func(w http.ResponseWriter, r *http.Request) {
		if !strings.Contains(r.URL.Path, "/rules/srd_monsters_grapple-rules/") {
			t.Fatalf("unexpected path %s", r.URL.Path)
		}
		_, _ = w.Write([]byte(`{
			"key": "srd_monsters_grapple-rules",
			"name": "Grapple Rules for Monsters",
			"desc": "Full text.",
			"document": "srd-2014"
		}`))
	})
	text := callTool(t, srv, "get_rules_section", map[string]any{"key": "srd_monsters_grapple-rules"})
	if !strings.Contains(text, "Full text.") {
		t.Fatalf("unexpected rule output: %s", text)
	}
}

func TestSearchRulesReferenceRequiresQuery(t *testing.T) {
	srv := testServerWithOpen5e(t, func(w http.ResponseWriter, r *http.Request) {})
	raw, _ := json.Marshal(map[string]any{})
	_, rerr := srv.handleSearchRulesReference(context.Background(), raw)
	if rerr == nil || !strings.Contains(rerr.Message, "query") {
		t.Fatalf("expected query error, got %v", rerr)
	}
}
