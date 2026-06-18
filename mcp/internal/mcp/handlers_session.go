package mcp

import (
	"context"
	"encoding/json"
	"strings"
)

func (s *Server) handleStartSession(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Title      string `json:"title"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	sess, err := s.Store.StartSession(ctx, cid, in.Title)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	s.activeSessionID = sess.ID
	return toolResultText(sess), nil
}

func (s *Server) handleEndSession(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		SessionID string `json:"session_id"`
	}
	_ = json.Unmarshal(args, &in)
	id := strings.TrimSpace(in.SessionID)
	if id == "" {
		id = s.activeSessionID
	}
	if id == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "session_id required"}
	}
	sess, err := s.Store.EndSession(ctx, id)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	if s.activeSessionID == id {
		s.activeSessionID = ""
	}
	return toolResultText(sess), nil
}
