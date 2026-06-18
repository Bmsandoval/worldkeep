package mcp

import (
	"context"
	"encoding/json"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

type dashboardInput struct {
	CampaignID string `json:"campaign_id"`
	EventLimit int    `json:"event_limit"`
	Scope      string `json:"scope"`
}

func readScopeArg(s *Server, scope string) (store.ReadScope, *rpcError) {
	sc := store.ParseScope(scope)
	if sc == store.ScopeDM && s.Role == "player" {
		return sc, &rpcError{Code: codeInvalidParams, Message: "player role cannot use dm scope"}
	}
	return sc, nil
}

func (s *Server) buildCampaignDashboard(ctx context.Context, in dashboardInput) (map[string]any, *rpcError) {
	cid := campaignIDArg(s, in.CampaignID)
	limit := in.EventLimit
	if limit <= 0 {
		limit = 5
	}
	sc, rerr := readScopeArg(s, in.Scope)
	if rerr != nil {
		return nil, rerr
	}

	campaign, err := s.Store.GetCampaign(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: "campaign not found: " + err.Error()}
	}

	var openSession *store.Session
	if sess, err := s.Store.GetOpenSession(ctx, cid); err == nil {
		openSession = &sess
	}

	plots, err := s.Store.ListActivePlots(ctx, cid, sc)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}

	events, err := s.Store.GetRecentEvents(ctx, cid, limit)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}

	pending, err := s.Store.ListPendingUpdates(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}

	type pendingItem struct {
		store.PendingUpdate
		Warnings []store.ConflictWarning `json:"warnings"`
	}
	pendingEnriched := make([]pendingItem, 0, len(pending))
	var continuityWarnings []store.ConflictWarning
	for _, u := range pending {
		var changes []store.WorldChange
		_ = json.Unmarshal(u.ProposedChanges, &changes)
		warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
		pendingEnriched = append(pendingEnriched, pendingItem{PendingUpdate: u, Warnings: warnings})
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

func (s *Server) handleGetCampaignDashboard(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in dashboardInput
	_ = json.Unmarshal(args, &in)
	dash, rerr := s.buildCampaignDashboard(ctx, in)
	if rerr != nil {
		if strings.HasPrefix(rerr.Message, "campaign not found") {
			return toolResultError(rerr.Message), nil
		}
		return nil, rerr
	}
	return toolResultText(dash), nil
}

func (s *Server) handlePrepareSessionBrief(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in dashboardInput
	_ = json.Unmarshal(args, &in)
	dash, rerr := s.buildCampaignDashboard(ctx, in)
	if rerr != nil {
		if strings.HasPrefix(rerr.Message, "campaign not found") {
			return toolResultError(rerr.Message), nil
		}
		return nil, rerr
	}
	campaign, _ := dash["campaign"].(store.Campaign)
	return toolResultText(map[string]any{
		"campaign":             campaign,
		"open_session":         dash["open_session"],
		"active_plots":         dash["active_plots"],
		"recent_events":        dash["recent_events"],
		"pending_update_count": dash["pending_update_count"],
		"continuity_warnings":  dash["continuity_warnings"],
		"scope":                dash["scope"],
	}), nil
}
