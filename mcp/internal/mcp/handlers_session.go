package mcp

import (
	"context"
	"encoding/json"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func (s *Server) handleStartSession(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Title      string `json:"title"`
		Notes      string `json:"notes"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	sess, err := s.Store.StartSession(ctx, cid, in.Title)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	if strings.TrimSpace(in.Notes) != "" {
		sess, err = s.Store.UpdateSessionNotes(ctx, sess.ID, in.Notes)
		if err != nil {
			return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
		}
	}
	s.activeSessionID = sess.ID
	return toolResultText(sess), nil
}

func (s *Server) handleEndSession(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		SessionID string          `json:"session_id"`
		Summary   string          `json:"summary"`
		Notes     string          `json:"notes"`
		Changes   json.RawMessage `json:"changes"`
		Reason    string          `json:"reason"`
	}
	_ = json.Unmarshal(args, &in)
	id := strings.TrimSpace(in.SessionID)
	if id == "" {
		id = s.activeSessionID
	}
	if id == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "session_id required"}
	}
	if strings.TrimSpace(in.Notes) != "" {
		if _, err := s.Store.UpdateSessionNotes(ctx, id, in.Notes); err != nil {
			return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
		}
	}
	sess, err := s.Store.EndSession(ctx, id, in.Summary)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	if s.activeSessionID == id {
		s.activeSessionID = ""
	}

	out := map[string]any{"session": sess}
	if strings.TrimSpace(in.Summary) != "" {
		out["session_summary"] = in.Summary
	}
	if len(in.Changes) > 0 {
		cid := campaignIDArg(s, "")
		var changes []store.WorldChange
		if err := json.Unmarshal(in.Changes, &changes); err != nil {
			return nil, &rpcError{Code: codeInvalidParams, Message: "changes must be a JSON array"}
		}
		reason := in.Reason
		if reason == "" {
			reason = in.Summary
		}
		if reason == "" {
			reason = "End-of-session canon updates"
		}
		pending, err := s.Store.ProposeWorldUpdate(ctx, cid, changes, reason)
		if err != nil {
			return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
		}
		warnings, _ := s.Store.CheckForConflicts(ctx, cid, changes)
		out["pending_update"] = pending
		out["warnings"] = warnings
		out["canon_review_queue"] = changes
	}
	return toolResultText(out), nil
}
