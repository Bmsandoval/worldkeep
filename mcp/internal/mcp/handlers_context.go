package mcp

import (
	"context"
	"encoding/json"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

func tokenizePrompt(prompt string) []string {
	words := strings.Fields(strings.ToLower(prompt))
	var tokens []string
	for _, w := range words {
		w = strings.Trim(w, ".,!?\"'")
		if len(w) >= 3 {
			tokens = append(tokens, w)
		}
	}
	return tokens
}

func (s *Server) handleCompileSceneContext(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Prompt     string `json:"prompt"`
		Limit      int    `json:"limit"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Prompt) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "prompt required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	limit := in.Limit
	if limit <= 0 {
		limit = 10
	}

	entities, facts, err := s.Store.SearchWorld(ctx, cid, in.Prompt, limit)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}

	tokens := tokenizePrompt(in.Prompt)
	for _, entityType := range []string{"npc", "location", "faction"} {
		all, listErr := s.Store.ListEntitiesByType(ctx, cid, entityType)
		if listErr != nil {
			continue
		}
	entitiesLoop:
		for _, e := range all {
			for _, existing := range entities {
				if existing.ID == e.ID {
					continue entitiesLoop
				}
			}
			name := strings.ToLower(e.Name)
			for _, tok := range tokens {
				if strings.Contains(name, tok) {
					entities = append(entities, e)
					break
				}
			}
		}
	}

	plots, err := s.Store.ListActivePlots(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	events, err := s.Store.GetRecentEvents(ctx, cid, limit)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	rulings, err := s.Store.SearchRulings(ctx, cid, in.Prompt, 5)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}

	return toolResultText(map[string]any{
		"prompt":    in.Prompt,
		"actors":    filterEntitiesByTypes(entities, "npc", "faction"),
		"locations": filterEntitiesByTypes(entities, "location"),
		"facts":     facts,
		"plots":     plots,
		"events":    events,
		"rulings":   rulings,
	}), nil
}

func filterEntitiesByTypes(items []store.Entity, types ...string) []store.Entity {
	allowed := map[string]bool{}
	for _, t := range types {
		allowed[t] = true
	}
	var out []store.Entity
	for _, e := range items {
		if allowed[e.Type] {
			out = append(out, e)
		}
	}
	return out
}

func (s *Server) handleGetRecentEvents(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Limit      int    `json:"limit"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	events, err := s.Store.GetRecentEvents(ctx, cid, in.Limit)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(events), nil
}

func (s *Server) handleGetActivePlots(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
	}
	_ = json.Unmarshal(args, &in)
	cid := campaignIDArg(s, in.CampaignID)
	plots, err := s.Store.ListActivePlots(ctx, cid)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(plots), nil
}

func (s *Server) handleSearchRulings(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		CampaignID string `json:"campaign_id"`
		Query      string `json:"query"`
		Limit      int    `json:"limit"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Query) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "query required"}
	}
	cid := campaignIDArg(s, in.CampaignID)
	rulings, err := s.Store.SearchRulings(ctx, cid, in.Query, in.Limit)
	if err != nil {
		return nil, &rpcError{Code: codeInternalError, Message: err.Error()}
	}
	return toolResultText(rulings), nil
}
