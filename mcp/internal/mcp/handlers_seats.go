package mcp

import (
	"context"
	"encoding/json"
	"strings"
)

func (s *Server) requireSeatAdmin() *rpcError {
	if s.Role == "player" {
		return &rpcError{Code: codeInvalidParams, Message: "player role cannot manage seats"}
	}
	return nil
}

func (s *Server) handleListCampaignSeats(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)

	seats, err := s.Store.ListCampaignSeats(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(map[string]any{
		"campaign_id": cid,
		"seats":       seats,
	}), nil
}

func (s *Server) handleGetSeat(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		SeatID string `json:"seat_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.SeatID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "seat_id required"}
	}
	seat, err := s.Store.GetSeat(ctx, strings.TrimSpace(in.SeatID))
	if err != nil {
		return toolResultError("seat not found"), nil
	}
	return toolResultText(seat), nil
}

func (s *Server) handleCreatePlayerSeat(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	if rerr := s.requireSeatAdmin(); rerr != nil {
		return nil, rerr
	}
	var in struct {
		CampaignID  string `json:"campaign_id"`
		ActorID     string `json:"actor_id"`
		DisplayName string `json:"display_name"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.ActorID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "actor_id required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	seat, err := s.Store.CreatePlayerSeat(ctx, cid, in.ActorID, in.DisplayName)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(seat), nil
}

func (s *Server) handleAssignSeatController(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	if rerr := s.requireSeatAdmin(); rerr != nil {
		return nil, rerr
	}
	var in struct {
		SeatID     string `json:"seat_id"`
		Controller string `json:"controller"`
		UserID     string `json:"controller_user_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.SeatID) == "" || strings.TrimSpace(in.Controller) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "seat_id and controller required"}
	}

	var userID *string
	if strings.TrimSpace(in.UserID) != "" {
		uid := strings.TrimSpace(in.UserID)
		userID = &uid
	}

	seat, err := s.Store.AssignSeatController(ctx, strings.TrimSpace(in.SeatID), in.Controller, userID)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(seat), nil
}
