package mcp

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/importmd"
	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
	"github.com/google/uuid"
)

func (s *Server) handleCreateSecret(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	if s.Role == "player" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "player role cannot create secrets"}
	}
	var in struct {
		CampaignID string `json:"campaign_id"`
		Title      string `json:"title"`
		Text       string `json:"text"`
		Reason     string `json:"reason"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Title) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "title required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	summary := in.Text
	if summary == "" {
		summary = in.Title
	}
	reason := in.Reason
	if reason == "" {
		reason = "Create DM secret: " + in.Title
	}
	changes := []store.WorldChange{{
		Op: "create_entity",
		Entity: &store.Entity{
			ID:         "secret_" + uuid.NewString()[:8],
			CampaignID: cid,
			Type:       "secret",
			Name:       in.Title,
			Summary:    summary,
			Data:       json.RawMessage(`{"visibility":"dm_only"}`),
		},
	}}
	raw, _ := json.Marshal(map[string]any{
		"campaign_id": cid,
		"changes":     changes,
		"reason":      reason,
	})
	return s.handleProposeWorldUpdate(ctx, raw)
}

func (s *Server) handleGetSession(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		SessionID string `json:"session_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.SessionID) == "" {
		id := s.activeSessionID
		if id == "" {
			return nil, &rpcError{Code: codeInvalidParams, Message: "session_id required"}
		}
		in.SessionID = id
	}
	ws, err := s.Store.GetSessionWorkspace(ctx, in.SessionID)
	if err != nil {
		return toolResultError(fmt.Sprintf("session not found: %v", err)), nil
	}
	pending, _ := s.Store.ListPendingUpdates(ctx, ws.Session.CampaignID)
	return toolResultText(map[string]any{
		"workspace":       ws,
		"pending_updates": pending,
	}), nil
}

func (s *Server) handleImportCampaignMarkdown(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	if s.Role == "player" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "player role cannot import campaigns"}
	}
	var in struct {
		CampaignID string `json:"campaign_id"`
		Markdown   string `json:"markdown"`
		Reason     string `json:"reason"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Markdown) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "markdown required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	entities := importmd.Parse(in.Markdown)
	if len(entities) == 0 {
		return toolResultError("no entities parsed from markdown"), nil
	}
	var changes []store.WorldChange
	for _, e := range entities {
		e.CampaignID = cid
		if e.ID == "" {
			e.ID = e.Type + "_" + uuid.NewString()[:8]
		}
		ent := e
		changes = append(changes, store.WorldChange{Op: "create_entity", Entity: &ent})
	}
	reason := in.Reason
	if reason == "" {
		reason = fmt.Sprintf("Import %d entities from markdown", len(changes))
	}
	raw, _ := json.Marshal(map[string]any{
		"campaign_id": cid,
		"changes":     changes,
		"reason":      reason,
	})
	return s.handleProposeWorldUpdate(ctx, raw)
}

func (s *Server) handleSetCampaignRole(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Role       string `json:"role"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Role) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "role required (owner, dm, player)"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	if err := s.Store.SetCampaignRole(ctx, cid, in.Role); err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(map[string]any{"campaign_id": cid, "role": in.Role}), nil
}
