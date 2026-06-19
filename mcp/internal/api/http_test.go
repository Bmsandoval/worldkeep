package api_test

import (
	"bytes"
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"path/filepath"
	"testing"

	"github.com/Bmsandoval/worldkeep/mcp/internal/api"
	"github.com/Bmsandoval/worldkeep/mcp/internal/seed"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func testServer(t *testing.T) (*api.Server, *store.Store) {
	t.Helper()
	dir := t.TempDir()
	st, err := store.Open(filepath.Join(dir, "test.sqlite"))
	if err != nil {
		t.Fatalf("open: %v", err)
	}
	t.Cleanup(func() { _ = st.Close() })
	_ = seed.Blackport(context.Background(), st)

	svc := &api.Service{Store: st, CampaignID: seed.DemoCampaignID, Role: "dm"}
	return &api.Server{Svc: svc}, st
}

func TestRESTDashboard(t *testing.T) {
	srv, _ := testServer(t)
	req := httptest.NewRequest(http.MethodGet, "/api/v1/campaigns/"+seed.DemoCampaignID+"/dashboard", nil)
	rec := httptest.NewRecorder()
	srv.Handler().ServeHTTP(rec, req)
	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d body=%s", rec.Code, rec.Body.String())
	}
	var dash map[string]any
	if err := json.Unmarshal(rec.Body.Bytes(), &dash); err != nil {
		t.Fatalf("decode: %v", err)
	}
	if dash["campaign"] == nil {
		t.Fatal("expected campaign")
	}
}

func TestRESTSearchAndEntity(t *testing.T) {
	srv, _ := testServer(t)
	h := srv.Handler()

	req := httptest.NewRequest(http.MethodGet, "/api/v1/campaigns/"+seed.DemoCampaignID+"/search?q=Finn", nil)
	rec := httptest.NewRecorder()
	h.ServeHTTP(rec, req)
	if rec.Code != http.StatusOK {
		t.Fatalf("search status = %d", rec.Code)
	}

	req2 := httptest.NewRequest(http.MethodGet, "/api/v1/entities/npc_finn", nil)
	rec2 := httptest.NewRecorder()
	h.ServeHTTP(rec2, req2)
	if rec2.Code != http.StatusOK {
		t.Fatalf("entity status = %d body=%s", rec2.Code, rec2.Body.String())
	}
}

func TestRESTPendingCommitReject(t *testing.T) {
	srv, st := testServer(t)
	h := srv.Handler()
	ctx := context.Background()

	changes := []store.WorldChange{{
		Op: "add_fact",
		Fact: &store.Fact{
			CampaignID: seed.DemoCampaignID,
			EntityID:   strPtr("npc_finn"),
			Text:       "REST API integration test fact.",
			Visibility: "party_known",
			Confidence: "high",
		},
	}}
	body, _ := json.Marshal(map[string]any{"changes": changes, "reason": "test"})
	req := httptest.NewRequest(http.MethodPost, "/api/v1/campaigns/"+seed.DemoCampaignID+"/pending-updates", bytes.NewReader(body))
	rec := httptest.NewRecorder()
	h.ServeHTTP(rec, req)
	if rec.Code != http.StatusCreated {
		t.Fatalf("propose status = %d body=%s", rec.Code, rec.Body.String())
	}
	var proposed struct {
		PendingUpdate store.PendingUpdate `json:"pending_update"`
	}
	_ = json.Unmarshal(rec.Body.Bytes(), &proposed)

	reqC := httptest.NewRequest(http.MethodPost, "/api/v1/pending-updates/"+proposed.PendingUpdate.ID+"/commit", nil)
	recC := httptest.NewRecorder()
	h.ServeHTTP(recC, reqC)
	if recC.Code != http.StatusOK {
		t.Fatalf("commit status = %d body=%s", recC.Code, recC.Body.String())
	}

	_, facts, err := st.SearchWorld(ctx, seed.DemoCampaignID, "REST API integration", 5, store.ScopeParty)
	if err != nil || len(facts) == 0 {
		t.Fatal("expected committed fact")
	}

	changes2 := changes
	body2, _ := json.Marshal(map[string]any{"changes": changes2})
	req2 := httptest.NewRequest(http.MethodPost, "/api/v1/campaigns/"+seed.DemoCampaignID+"/pending-updates", bytes.NewReader(body2))
	rec2 := httptest.NewRecorder()
	h.ServeHTTP(rec2, req2)
	var proposed2 struct {
		PendingUpdate store.PendingUpdate `json:"pending_update"`
	}
	_ = json.Unmarshal(rec2.Body.Bytes(), &proposed2)

	reqR := httptest.NewRequest(http.MethodPost, "/api/v1/pending-updates/"+proposed2.PendingUpdate.ID+"/reject", bytes.NewReader([]byte(`{"reason":"no"}`)))
	recR := httptest.NewRecorder()
	h.ServeHTTP(recR, reqR)
	if recR.Code != http.StatusOK {
		t.Fatalf("reject status = %d", recR.Code)
	}
}

func TestRESTSessions(t *testing.T) {
	srv, st := testServer(t)
	h := srv.Handler()
	ctx := context.Background()

	sess, err := st.StartSession(ctx, seed.DemoCampaignID, "REST session test")
	if err != nil {
		t.Fatalf("start session: %v", err)
	}

	req := httptest.NewRequest(http.MethodGet, "/api/v1/campaigns/"+seed.DemoCampaignID+"/sessions", nil)
	rec := httptest.NewRecorder()
	h.ServeHTTP(rec, req)
	if rec.Code != http.StatusOK {
		t.Fatalf("list sessions status = %d body=%s", rec.Code, rec.Body.String())
	}

	req2 := httptest.NewRequest(http.MethodGet, "/api/v1/sessions/"+sess.ID, nil)
	rec2 := httptest.NewRecorder()
	h.ServeHTTP(rec2, req2)
	if rec2.Code != http.StatusOK {
		t.Fatalf("session workspace status = %d body=%s", rec2.Code, rec2.Body.String())
	}
	var ws store.SessionWorkspace
	if err := json.Unmarshal(rec2.Body.Bytes(), &ws); err != nil {
		t.Fatalf("decode: %v", err)
	}
	if ws.Session.ID != sess.ID {
		t.Fatalf("session id = %q", ws.Session.ID)
	}
}

func TestRESTAuthBearer(t *testing.T) {
	srv, _ := testServer(t)
	srv.Auth = api.AuthConfig{Token: "secret", Role: "dm"}

	req := httptest.NewRequest(http.MethodGet, "/api/v1/campaigns/"+seed.DemoCampaignID+"/dashboard", nil)
	rec := httptest.NewRecorder()
	srv.Handler().ServeHTTP(rec, req)
	if rec.Code != http.StatusForbidden {
		t.Fatalf("expected 403 without token, got %d", rec.Code)
	}

	req2 := httptest.NewRequest(http.MethodGet, "/api/v1/campaigns/"+seed.DemoCampaignID+"/dashboard", nil)
	req2.Header.Set("Authorization", "Bearer secret")
	rec2 := httptest.NewRecorder()
	srv.Handler().ServeHTTP(rec2, req2)
	if rec2.Code != http.StatusOK {
		t.Fatalf("expected 200 with token, got %d", rec2.Code)
	}
}

func strPtr(s string) *string { return &s }
