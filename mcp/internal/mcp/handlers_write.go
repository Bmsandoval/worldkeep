package mcp

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func (s *Server) handleProposeWorldUpdate(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string          `json:"campaign_id"`
		Changes    json.RawMessage `json:"changes"`
		Reason     string          `json:"reason"`
	}
	if err := json.Unmarshal(args, &in); err != nil || len(in.Changes) == 0 {
		return nil, &rpcError{Code: codeInvalidParams, Message: "changes required"}
	}
	cid := campaignIDArg(s, in.CampaignID)

	var changes []store.WorldChange
	if err := json.Unmarshal(in.Changes, &changes); err != nil {
		return nil, &rpcError{Code: codeInvalidParams, Message: "changes must be a JSON array"}
	}

	pending, err := s.Store.ProposeWorldUpdate(ctx, cid, changes, in.Reason)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
	return toolResultText(map[string]any{
		"pending_update": pending,
		"warnings":       warnings,
	}), nil
}

func (s *Server) handleListPendingUpdates(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	list, err := s.Store.ListPendingUpdates(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	type item struct {
		store.PendingUpdate
		Warnings []store.ConflictWarning `json:"warnings"`
	}
	var enriched []item
	for _, u := range list {
		var changes []store.WorldChange
		_ = json.Unmarshal(u.ProposedChanges, &changes)
		warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
		enriched = append(enriched, item{PendingUpdate: u, Warnings: warnings})
	}
	return toolResultText(enriched), nil
}

func (s *Server) handleCommitWorldUpdate(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		UpdateID string `json:"update_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.UpdateID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "update_id required"}
	}
	if s.Role == "player" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "player role cannot commit canon updates"}
	}
	pending, err := s.Store.GetPendingUpdate(ctx, in.UpdateID)
	if err != nil {
		return toolResultError(fmt.Sprintf("commit failed: %v", err)), nil
	}
	var changes []store.WorldChange
	_ = json.Unmarshal(pending.ProposedChanges, &changes)

	updated, err := s.Store.CommitWorldUpdate(ctx, in.UpdateID)
	if err != nil {
		return toolResultError(fmt.Sprintf("commit failed: %v", err)), nil
	}
	if s.activeSessionID != "" {
		for _, ch := range changes {
			if eid := entityIDFromChange(ch); eid != "" {
				_ = s.Store.RecordSessionChange(ctx, s.activeSessionID, eid, ch.Op)
			}
		}
	}
	return toolResultText(updated), nil
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

func (s *Server) handleRejectWorldUpdate(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		UpdateID string `json:"update_id"`
		Reason   string `json:"reason"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.UpdateID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "update_id required"}
	}
	if err := s.Store.RejectWorldUpdate(ctx, in.UpdateID, in.Reason); err != nil {
		return toolResultError(fmt.Sprintf("reject failed: %v", err)), nil
	}
	return toolResultText(map[string]string{"status": "rejected", "update_id": in.UpdateID}), nil
}

func (s *Server) handleCheckForConflicts(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string          `json:"campaign_id"`
		Changes    json.RawMessage `json:"changes"`
	}
	if err := json.Unmarshal(args, &in); err != nil || len(in.Changes) == 0 {
		return nil, &rpcError{Code: codeInvalidParams, Message: "changes required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	var changes []store.WorldChange
	if err := json.Unmarshal(in.Changes, &changes); err != nil {
		return nil, &rpcError{Code: codeInvalidParams, Message: "changes must be a JSON array"}
	}
	warnings, err := s.Store.CheckForConflicts(ctx, cid, changes)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(map[string]any{"warnings": warnings}), nil
}