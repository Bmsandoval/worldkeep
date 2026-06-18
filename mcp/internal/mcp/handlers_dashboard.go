package mcp

import (
	"context"
	"encoding/json"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func (s *Server) handleGetCampaignDashboard(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		EventLimit int    `json:"event_limit"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	limit := in.EventLimit
	if limit <= 0 {
		limit = 5
	}

	campaign, err := s.Store.GetCampaign(ctx, cid)
	if err != nil {
		return toolResultError("campaign not found: " + err.Error()), nil
	}

	var openSession *store.Session
	if sess, err := s.Store.GetOpenSession(ctx, cid); err == nil {
		openSession = &sess
	}

	plots, err := s.Store.ListActivePlots(ctx, cid)
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

	return toolResultText(map[string]any{
		"campaign":             campaign,
		"open_session":         openSession,
		"active_plots":         plots,
		"recent_events":        events,
		"pending_updates":      pendingEnriched,
		"continuity_warnings":  continuityWarnings,
		"pending_update_count": len(pendingEnriched),
	}), nil
}
