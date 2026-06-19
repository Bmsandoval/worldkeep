package mcp

import (
	"context"
	"encoding/json"
	"strings"
)

func (s *Server) openSessionID(ctx context.Context, campaignID string) *string {
	if strings.TrimSpace(s.activeSessionID) != "" {
		id := s.activeSessionID
		return &id
	}
	if open, err := s.Store.GetOpenSession(ctx, campaignID); err == nil {
		id := open.ID
		return &id
	}
	return nil
}

func (s *Server) handleHandoffSeat(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	if rerr := s.requireSeatAdmin(); rerr != nil {
		return nil, rerr
	}
	var in struct {
		SeatID     string `json:"seat_id"`
		Controller string `json:"controller"`
		UserID     string `json:"controller_user_id"`
		Reason     string `json:"reason"`
		SessionID  string `json:"session_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.SeatID) == "" || strings.TrimSpace(in.Controller) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "seat_id and controller required"}
	}

	var userID *string
	if strings.TrimSpace(in.UserID) != "" {
		uid := strings.TrimSpace(in.UserID)
		userID = &uid
	}

	seat, err := s.Store.GetSeat(ctx, strings.TrimSpace(in.SeatID))
	if err != nil {
		return toolResultError("seat not found"), nil
	}

	var sessionID *string
	if strings.TrimSpace(in.SessionID) != "" {
		sid := strings.TrimSpace(in.SessionID)
		sessionID = &sid
	} else {
		sessionID = s.openSessionID(ctx, seat.CampaignID)
	}

	result, err := s.Store.HandoffSeat(ctx, strings.TrimSpace(in.SeatID), in.Controller, userID, in.Reason, sessionID)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(result), nil
}

func (s *Server) handleReleaseSeatToAI(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	if rerr := s.requireSeatAdmin(); rerr != nil {
		return nil, rerr
	}
	var in struct {
		SeatID    string `json:"seat_id"`
		Reason    string `json:"reason"`
		SessionID string `json:"session_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.SeatID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "seat_id required"}
	}

	seat, err := s.Store.GetSeat(ctx, strings.TrimSpace(in.SeatID))
	if err != nil {
		return toolResultError("seat not found"), nil
	}

	var sessionID *string
	if strings.TrimSpace(in.SessionID) != "" {
		sid := strings.TrimSpace(in.SessionID)
		sessionID = &sid
	} else {
		sessionID = s.openSessionID(ctx, seat.CampaignID)
	}

	result, err := s.Store.ReleaseSeatToAI(ctx, strings.TrimSpace(in.SeatID), in.Reason, sessionID)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(result), nil
}

func (s *Server) handleGetSessionFloor(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		SessionID string `json:"session_id"`
	}
	_ = json.Unmarshal(args, &in)
	id := strings.TrimSpace(in.SessionID)
	if id == "" {
		id = s.activeSessionID
	}
	if id == "" {
		open, err := s.Store.GetOpenSession(ctx, s.CampaignID)
		if err != nil {
			return toolResultError("no open session"), nil
		}
		id = open.ID
	}
	floor, err := s.Store.GetSessionFloor(ctx, id)
	if err != nil {
		return toolResultError("session floor not found"), nil
	}
	return toolResultText(floor), nil
}
