package api

import (
	"context"
	"encoding/json"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

type Service struct {
	Store      *store.Store
	CampaignID string
	Role       string
}

type DashboardInput struct {
	CampaignID string
	EventLimit int
	Scope      string
}

type PendingItem struct {
	store.PendingUpdate
	Warnings []store.ConflictWarning `json:"warnings"`
}

func (s *Service) campaignID(raw string) string {
	id := strings.TrimSpace(raw)
	if id == "" {
		return s.CampaignID
	}
	return id
}

func (s *Service) readScope(scope string) (store.ReadScope, *Error) {
	sc := store.ParseScope(scope)
	if sc == store.ScopeDM && s.Role == "player" {
		return sc, errForbidden("player role cannot use dm scope")
	}
	return sc, nil
}

func (s *Service) CampaignDashboard(ctx context.Context, in DashboardInput) (map[string]any, *Error) {
	cid := s.campaignID(in.CampaignID)
	limit := in.EventLimit
	if limit <= 0 {
		limit = 5
	}
	sc, aerr := s.readScope(in.Scope)
	if aerr != nil {
		return nil, aerr
	}

	campaign, err := s.Store.GetCampaign(ctx, cid)
	if err != nil {
		return nil, errNotFound("campaign not found")
	}

	var openSession *store.Session
	if sess, err := s.Store.GetOpenSession(ctx, cid); err == nil {
		openSession = &sess
	}

	plots, err := s.Store.ListActivePlots(ctx, cid, sc)
	if err != nil {
		return nil, errInternal(err)
	}

	events, err := s.Store.GetRecentEvents(ctx, cid, limit)
	if err != nil {
		return nil, errInternal(err)
	}

	pending, err := s.Store.ListPendingUpdates(ctx, cid)
	if err != nil {
		return nil, errInternal(err)
	}

	pendingEnriched := make([]PendingItem, 0, len(pending))
	var continuityWarnings []store.ConflictWarning
	for _, u := range pending {
		var changes []store.WorldChange
		_ = json.Unmarshal(u.ProposedChanges, &changes)
		warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
		pendingEnriched = append(pendingEnriched, PendingItem{PendingUpdate: u, Warnings: warnings})
		continuityWarnings = append(continuityWarnings, warnings...)
	}

	return map[string]any{
		"campaign":             campaign,
		"open_session":         openSession,
		"active_plots":         plots,
		"recent_events":        events,
		"pending_updates":      pendingEnriched,
		"continuity_warnings":  continuityWarnings,
		"pending_update_count": len(pendingEnriched),
		"scope":                string(sc),
	}, nil
}

func (s *Service) GetEntity(ctx context.Context, entityID, scope string) (store.Entity, *Error) {
	if strings.TrimSpace(entityID) == "" {
		return store.Entity{}, errBadRequest("invalid_params", "entity_id required")
	}
	sc, aerr := s.readScope(scope)
	if aerr != nil {
		return store.Entity{}, aerr
	}
	entity, err := s.Store.GetEntity(ctx, entityID)
	if err != nil {
		return store.Entity{}, errNotFound("entity not found")
	}
	if entity.Type == "secret" && !sc.IncludesDMOnly() {
		return store.Entity{}, errNotFound("entity not found")
	}
	return entity, nil
}

func (s *Service) ListEntities(ctx context.Context, campaignID, entityType, scope string) ([]store.Entity, *Error) {
	cid := s.campaignID(campaignID)
	sc, aerr := s.readScope(scope)
	if aerr != nil {
		return nil, aerr
	}
	list, err := s.Store.ListEntitiesByType(ctx, cid, entityType, sc)
	if err != nil {
		return nil, errInternal(err)
	}
	return list, nil
}

func (s *Service) ListSessions(ctx context.Context, campaignID string, limit int) ([]store.Session, *Error) {
	cid := s.campaignID(campaignID)
	list, err := s.Store.ListSessions(ctx, cid, limit)
	if err != nil {
		return nil, errInternal(err)
	}
	return list, nil
}

func (s *Service) GetSessionWorkspace(ctx context.Context, sessionID string) (store.SessionWorkspace, *Error) {
	if strings.TrimSpace(sessionID) == "" {
		return store.SessionWorkspace{}, errBadRequest("invalid_params", "session_id required")
	}
	ws, err := s.Store.GetSessionWorkspace(ctx, sessionID)
	if err != nil {
		return store.SessionWorkspace{}, errNotFound("session not found")
	}
	return ws, nil
}

type SearchInput struct {
	CampaignID string
	Query      string
	Limit      int
	Scope      string
	Hybrid     bool
}

func (s *Service) SearchWorld(ctx context.Context, in SearchInput) (map[string]any, *Error) {
	if strings.TrimSpace(in.Query) == "" {
		return nil, errBadRequest("invalid_params", "query required")
	}
	cid := s.campaignID(in.CampaignID)
	sc, aerr := s.readScope(in.Scope)
	if aerr != nil {
		return nil, aerr
	}
	limit := in.Limit
	if limit <= 0 {
		limit = 20
	}

	var entities []store.Entity
	var facts []store.Fact
	var err error
	if in.Hybrid {
		entities, facts, err = s.Store.SearchWorldHybrid(ctx, cid, in.Query, limit, sc)
	} else {
		entities, facts, err = s.Store.SearchWorld(ctx, cid, in.Query, limit, sc)
	}
	if err != nil {
		return nil, errInternal(err)
	}
	return map[string]any{
		"entities": entities,
		"facts":    facts,
		"scope":    string(sc),
		"hybrid":   in.Hybrid,
	}, nil
}

func (s *Service) ListPendingUpdates(ctx context.Context, campaignID string) ([]PendingItem, *Error) {
	cid := s.campaignID(campaignID)
	list, err := s.Store.ListPendingUpdates(ctx, cid)
	if err != nil {
		return nil, errInternal(err)
	}
	out := make([]PendingItem, 0, len(list))
	for _, u := range list {
		var changes []store.WorldChange
		_ = json.Unmarshal(u.ProposedChanges, &changes)
		warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
		out = append(out, PendingItem{PendingUpdate: u, Warnings: warnings})
	}
	return out, nil
}

func (s *Service) CommitWorldUpdate(ctx context.Context, updateID, sessionID string) (store.PendingUpdate, *Error) {
	if strings.TrimSpace(updateID) == "" {
		return store.PendingUpdate{}, errBadRequest("invalid_params", "update_id required")
	}
	if s.Role == "player" {
		return store.PendingUpdate{}, errForbidden("player role cannot commit canon updates")
	}

	pending, err := s.Store.GetPendingUpdate(ctx, updateID)
	if err != nil {
		return store.PendingUpdate{}, errNotFound("pending update not found")
	}

	var changes []store.WorldChange
	_ = json.Unmarshal(pending.ProposedChanges, &changes)

	updated, err := s.Store.CommitWorldUpdate(ctx, updateID)
	if err != nil {
		return store.PendingUpdate{}, errInternal(err)
	}

	sid := strings.TrimSpace(sessionID)
	if sid == "" {
		if open, err := s.Store.GetOpenSession(ctx, pending.CampaignID); err == nil {
			sid = open.ID
		}
	}
	if sid != "" {
		for _, ch := range changes {
			if eid := entityIDFromChange(ch); eid != "" {
				_ = s.Store.RecordSessionChange(ctx, sid, eid, ch.Op)
			}
		}
	}
	return updated, nil
}

func (s *Service) RejectWorldUpdate(ctx context.Context, updateID, reason string) *Error {
	if strings.TrimSpace(updateID) == "" {
		return errBadRequest("invalid_params", "update_id required")
	}
	if err := s.Store.RejectWorldUpdate(ctx, updateID, reason); err != nil {
		return errNotFound("pending update not found")
	}
	return nil
}

func (s *Service) ProposeWorldUpdate(ctx context.Context, campaignID string, changes []store.WorldChange, reason string) (map[string]any, *Error) {
	if len(changes) == 0 {
		return nil, errBadRequest("invalid_params", "changes required")
	}
	cid := s.campaignID(campaignID)
	pending, err := s.Store.ProposeWorldUpdate(ctx, cid, changes, reason)
	if err != nil {
		return nil, errInternal(err)
	}
	warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
	return map[string]any{
		"pending_update": pending,
		"warnings":       warnings,
	}, nil
}

func entityIDFromChange(ch store.WorldChange) string {
	switch ch.Op {
	case "add_fact":
		if ch.Fact != nil && ch.Fact.EntityID != nil {
			return *ch.Fact.EntityID
		}
	case "upsert_entity", "create_entity", "update_entity":
		if ch.Entity != nil {
			return ch.Entity.ID
		}
		return ch.EntityID
	}
	return ""
}
