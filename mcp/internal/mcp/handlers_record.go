package mcp

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
	"github.com/google/uuid"
)

func (s *Server) handleRecordEvent(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string          `json:"campaign_id"`
		SessionID  string          `json:"session_id"`
		Title      string          `json:"title"`
		Summary    string          `json:"summary"`
		EntityIDs  json.RawMessage `json:"entity_ids"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Title) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "title and summary required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	sid := strings.TrimSpace(in.SessionID)
	if sid == "" {
		sid = s.activeSessionID
	}
	var sessionID *string
	if sid != "" {
		sessionID = &sid
	}
	ev := store.Event{
		ID: uuid.NewString(), CampaignID: cid, SessionID: sessionID,
		Title: in.Title, Summary: in.Summary, EntityIDs: in.EntityIDs,
	}
	if len(ev.EntityIDs) == 0 {
		ev.EntityIDs = json.RawMessage("[]")
	}
	ev.ID = "event_" + ev.ID[:8]
	if err := s.Store.AddEvent(ctx, ev); err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(ev), nil
}

func (s *Server) handleRecordRuling(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Question   string `json:"question"`
		Answer     string `json:"answer"`
		Scope      string `json:"scope"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Question) == "" || strings.TrimSpace(in.Answer) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "question and answer required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	scope := in.Scope
	if scope == "" {
		scope = "campaign"
	}
	r := store.Ruling{
		ID: "ruling_" + uuid.NewString()[:8], CampaignID: cid,
		Question: in.Question, Answer: in.Answer, Scope: scope,
	}
	if err := s.Store.AddRuling(ctx, r); err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: fmt.Sprintf("record ruling: %v", err)}
	}
	return toolResultText(r), nil
}
