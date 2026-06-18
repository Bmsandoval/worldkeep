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
	return toolResultText(pending), nil
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
	return toolResultText(list), nil
}

func (s *Server) handleCommitWorldUpdate(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		UpdateID string `json:"update_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.UpdateID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "update_id required"}
	}
	updated, err := s.Store.CommitWorldUpdate(ctx, in.UpdateID)
	if err != nil {
		return toolResultError(fmt.Sprintf("commit failed: %v", err)), nil
	}
	return toolResultText(updated), nil
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