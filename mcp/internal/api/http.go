package api

import (
	"encoding/json"
	"net/http"
	"os"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

type AuthConfig struct {
	Token string
	Role  string
}

func AuthFromEnv() AuthConfig {
	return AuthConfig{
		Token: strings.TrimSpace(os.Getenv("WORLDKEEP_API_TOKEN")),
		Role:  strings.TrimSpace(os.Getenv("WORLDKEEP_ROLE")),
	}
}

func (a AuthConfig) RoleOrDefault(fallback string) string {
	if a.Role != "" {
		return a.Role
	}
	return fallback
}

func (a AuthConfig) Middleware(next http.Handler) http.Handler {
	if a.Token == "" {
		return next
	}
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path == "/healthz" || strings.HasPrefix(r.URL.Path, "/mcp") {
			next.ServeHTTP(w, r)
			return
		}
		auth := r.Header.Get("Authorization")
		const prefix = "Bearer "
		if !strings.HasPrefix(auth, prefix) || strings.TrimSpace(auth[len(prefix):]) != a.Token {
			writeError(w, errForbidden("invalid or missing bearer token"))
			return
		}
		next.ServeHTTP(w, r)
	})
}

// CombinedHandler wraps a mux with CORS and optional bearer auth (MCP paths exempt).
func CombinedHandler(mux http.Handler, auth AuthConfig) http.Handler {
	return corsMiddleware(auth.Middleware(mux))
}

type Server struct {
	Svc  *Service
	Auth AuthConfig
}

func (s *Server) Mount(mux *http.ServeMux) {
	mux.HandleFunc("/healthz", s.handleHealthz)
	mux.HandleFunc("/api/v1/campaigns/", s.handleCampaignsPrefix)
	mux.HandleFunc("/api/v1/entities/", s.handleEntity)
	mux.HandleFunc("/api/v1/sessions/", s.handleSession)
	mux.HandleFunc("/api/v1/pending-updates/", s.handlePendingUpdate)
}

func (s *Server) Handler() http.Handler {
	mux := http.NewServeMux()
	s.Mount(mux)
	return CombinedHandler(mux, s.Auth)
}

func (s *Server) handleHealthz(w http.ResponseWriter, _ *http.Request) {
	writeJSON(w, http.StatusOK, map[string]string{"status": "ok"})
}

func (s *Server) handleCampaignsPrefix(w http.ResponseWriter, r *http.Request) {
	path := strings.TrimPrefix(r.URL.Path, "/api/v1/campaigns/")
	parts := strings.Split(strings.Trim(path, "/"), "/")
	if len(parts) < 2 {
		writeError(w, errNotFound("route not found"))
		return
	}
	campaignID := parts[0]
	resource := parts[1]

	switch resource {
	case "dashboard":
		if r.Method != http.MethodGet {
			methodNotAllowed(w, http.MethodGet)
			return
		}
		s.handleDashboard(w, r, campaignID)
	case "search":
		if r.Method != http.MethodGet {
			methodNotAllowed(w, http.MethodGet)
			return
		}
		s.handleSearch(w, r, campaignID)
	case "entities":
		if r.Method != http.MethodGet {
			methodNotAllowed(w, http.MethodGet)
			return
		}
		s.handleListEntities(w, r, campaignID)
	case "pending-updates":
		switch r.Method {
		case http.MethodGet:
			s.handleListPending(w, r, campaignID)
		case http.MethodPost:
			s.handlePropose(w, r, campaignID)
		default:
			methodNotAllowed(w, http.MethodGet, http.MethodPost)
		}
	case "sessions":
		if r.Method != http.MethodGet {
			methodNotAllowed(w, http.MethodGet)
			return
		}
		s.handleListSessions(w, r, campaignID)
	default:
		writeError(w, errNotFound("route not found"))
	}
}

func (s *Server) handleDashboard(w http.ResponseWriter, r *http.Request, campaignID string) {
	q := r.URL.Query()
	eventLimit := queryInt(q, "event_limit", 5)
	dash, err := s.Svc.CampaignDashboard(r.Context(), DashboardInput{
		CampaignID: campaignID,
		EventLimit: eventLimit,
		Scope:      q.Get("scope"),
	})
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, dash)
}

func (s *Server) handleSearch(w http.ResponseWriter, r *http.Request, campaignID string) {
	q := r.URL.Query()
	result, err := s.Svc.SearchWorld(r.Context(), SearchInput{
		CampaignID: campaignID,
		Query:      q.Get("q"),
		Limit:      queryInt(q, "limit", 20),
		Scope:      q.Get("scope"),
		Hybrid:     queryBool(q, "hybrid"),
	})
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, result)
}

func (s *Server) handleListEntities(w http.ResponseWriter, r *http.Request, campaignID string) {
	entityType := r.URL.Query().Get("type")
	list, err := s.Svc.ListEntities(r.Context(), campaignID, entityType, r.URL.Query().Get("scope"))
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"entities": list})
}

func (s *Server) handleEntity(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		methodNotAllowed(w, http.MethodGet)
		return
	}
	id := strings.TrimPrefix(r.URL.Path, "/api/v1/entities/")
	id = strings.Trim(id, "/")
	if id == "" {
		writeError(w, errBadRequest("invalid_params", "entity_id required"))
		return
	}
	entity, err := s.Svc.GetEntity(r.Context(), id, r.URL.Query().Get("scope"))
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, entity)
}

func (s *Server) handleListSessions(w http.ResponseWriter, r *http.Request, campaignID string) {
	limit := queryInt(r.URL.Query(), "limit", 20)
	list, err := s.Svc.ListSessions(r.Context(), campaignID, limit)
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"sessions": list})
}

func (s *Server) handleSession(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodGet {
		methodNotAllowed(w, http.MethodGet)
		return
	}
	id := strings.TrimPrefix(r.URL.Path, "/api/v1/sessions/")
	id = strings.Trim(id, "/")
	if id == "" {
		writeError(w, errBadRequest("invalid_params", "session_id required"))
		return
	}
	ws, err := s.Svc.GetSessionWorkspace(r.Context(), id)
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, ws)
}

func (s *Server) handleListPending(w http.ResponseWriter, r *http.Request, campaignID string) {
	list, err := s.Svc.ListPendingUpdates(r.Context(), campaignID)
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"pending_updates": list})
}

func (s *Server) handlePropose(w http.ResponseWriter, r *http.Request, campaignID string) {
	var body struct {
		Changes json.RawMessage `json:"changes"`
		Reason  string          `json:"reason"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		writeError(w, errBadRequest("invalid_json", "invalid JSON body"))
		return
	}
	var changes []store.WorldChange
	if err := json.Unmarshal(body.Changes, &changes); err != nil || len(changes) == 0 {
		writeError(w, errBadRequest("invalid_params", "changes must be a non-empty JSON array"))
		return
	}
	result, err := s.Svc.ProposeWorldUpdate(r.Context(), campaignID, changes, body.Reason)
	if err != nil {
		writeError(w, err)
		return
	}
	writeJSON(w, http.StatusCreated, result)
}

func (s *Server) handlePendingUpdate(w http.ResponseWriter, r *http.Request) {
	path := strings.TrimPrefix(r.URL.Path, "/api/v1/pending-updates/")
	parts := strings.Split(strings.Trim(path, "/"), "/")
	if len(parts) != 2 {
		writeError(w, errNotFound("route not found"))
		return
	}
	updateID, action := parts[0], parts[1]

	switch action {
	case "commit":
		if r.Method != http.MethodPost {
			methodNotAllowed(w, http.MethodPost)
			return
		}
		var body struct {
			SessionID string `json:"session_id"`
		}
		_ = json.NewDecoder(r.Body).Decode(&body)
		updated, err := s.Svc.CommitWorldUpdate(r.Context(), updateID, body.SessionID)
		if err != nil {
			writeError(w, err)
			return
		}
		writeJSON(w, http.StatusOK, updated)
	case "reject":
		if r.Method != http.MethodPost {
			methodNotAllowed(w, http.MethodPost)
			return
		}
		var body struct {
			Reason string `json:"reason"`
		}
		_ = json.NewDecoder(r.Body).Decode(&body)
		if err := s.Svc.RejectWorldUpdate(r.Context(), updateID, body.Reason); err != nil {
			writeError(w, err)
			return
		}
		writeJSON(w, http.StatusOK, map[string]string{"status": "rejected", "update_id": updateID})
	default:
		writeError(w, errNotFound("route not found"))
	}
}
