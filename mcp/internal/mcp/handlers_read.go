package mcp

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"
)

func campaignIDArg(s *Server, raw string) string {
	id := strings.TrimSpace(raw)
	if id == "" {
		return s.CampaignID
	}
	return id
}

func (s *Server) handleGetCampaignOverview(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)

	campaign, err := s.Store.GetCampaign(ctx, cid)
	if err != nil {
		return toolResultError(fmt.Sprintf("campaign not found: %v", err)), nil
	}
	plots, err := s.Store.ListActivePlots(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	npcs, _ := s.Store.ListEntitiesByType(ctx, cid, "npc")
	factions, _ := s.Store.ListEntitiesByType(ctx, cid, "faction")
	locations, _ := s.Store.ListEntitiesByType(ctx, cid, "location")

	return toolResultText(map[string]any{
		"campaign":  campaign,
		"plots":     plots,
		"npcs":      npcs,
		"factions":  factions,
		"locations": locations,
	}), nil
}

func (s *Server) handleGetEntity(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		EntityID string `json:"entity_id"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.EntityID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "entity_id required"}
	}
	entity, err := s.Store.GetEntity(ctx, in.EntityID)
	if err != nil {
		return toolResultError(fmt.Sprintf("entity not found: %v", err)), nil
	}
	return toolResultText(entity), nil
}

func (s *Server) handleSearchWorld(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Query      string `json:"query"`
		Limit      int    `json:"limit"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Query) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "query required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	entities, facts, err := s.Store.SearchWorld(ctx, cid, in.Query, in.Limit)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(map[string]any{
		"entities": entities,
		"facts":    facts,
	}), nil
}
