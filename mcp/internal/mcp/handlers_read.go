package mcp

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
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
		Scope      string `json:"scope"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	sc, rerr := readScopeArg(s, in.Scope)
	if rerr != nil {
		return nil, rerr
	}

	campaign, err := s.Store.GetCampaign(ctx, cid)
	if err != nil {
		return toolResultError(fmt.Sprintf("campaign not found: %v", err)), nil
	}
	plots, err := s.Store.ListActivePlots(ctx, cid, sc)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	npcs, _ := s.Store.ListEntitiesByType(ctx, cid, "npc", sc)
	factions, _ := s.Store.ListEntitiesByType(ctx, cid, "faction", sc)
	locations, _ := s.Store.ListEntitiesByType(ctx, cid, "location", sc)

	return toolResultText(map[string]any{
		"campaign":  campaign,
		"plots":     plots,
		"npcs":      npcs,
		"factions":  factions,
		"locations": locations,
		"scope":     string(sc),
	}), nil
}

func (s *Server) handleGetEntity(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		EntityID string `json:"entity_id"`
		Scope    string `json:"scope"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.EntityID) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "entity_id required"}
	}
	sc, rerr := readScopeArg(s, in.Scope)
	if rerr != nil {
		return nil, rerr
	}
	entity, err := s.Store.GetEntity(ctx, in.EntityID)
	if err != nil {
		return toolResultError(fmt.Sprintf("entity not found: %v", err)), nil
	}
	if entity.Type == "secret" && !sc.IncludesDMOnly() {
		return toolResultError("entity not found"), nil
	}
	return toolResultText(entity), nil
}

func (s *Server) handleSearchWorld(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Query      string `json:"query"`
		Limit      int    `json:"limit"`
		Scope      string `json:"scope"`
		Hybrid     bool   `json:"hybrid"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Query) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "query required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	sc, rerr := readScopeArg(s, in.Scope)
	if rerr != nil {
		return nil, rerr
	}
	var entities []store.Entity
	var facts []store.Fact
	var err error
	if in.Hybrid {
		entities, facts, err = s.Store.SearchWorldHybrid(ctx, cid, in.Query, in.Limit, sc)
	} else {
		entities, facts, err = s.Store.SearchWorld(ctx, cid, in.Query, in.Limit, sc)
	}
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(map[string]any{
		"entities": entities,
		"facts":    facts,
		"scope":    string(sc),
		"hybrid":   in.Hybrid,
	}), nil
}
