package mcp

import (
	"context"
	"encoding/json"
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/open5e"
)

func (s *Server) open5eClient() *open5e.Client {
	if s.Open5e != nil {
		return s.Open5e
	}
	return open5e.NewClient()
}

func (s *Server) handleSearchRulesReference(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Query string `json:"query"`
		Limit int    `json:"limit"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Query) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "query required"}
	}

	res, err := s.open5eClient().SearchRulesReference(ctx, in.Query, in.Limit)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(res), nil
}

func (s *Server) handleGetRulesSection(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Key string `json:"key"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Key) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "key required (Open5e rule key from search_rules_reference)"}
	}

	rule, err := s.open5eClient().GetRulesSection(ctx, in.Key)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(rule), nil
}

func (s *Server) handleSearchSpells(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Query string `json:"query"`
		Limit int    `json:"limit"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Query) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "query required"}
	}
	res, err := s.open5eClient().SearchSpells(ctx, in.Query, in.Limit)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(res), nil
}

func (s *Server) handleGetSpell(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Key  string `json:"key"`
		Name string `json:"name"`
	}
	if err := json.Unmarshal(args, &in); err != nil {
		return nil, &rpcError{Code: codeInvalidParams, Message: "invalid params"}
	}
	spell, err := s.open5eClient().ResolveSpell(ctx, in.Key, in.Name)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(spell), nil
}

func (s *Server) handleSearchCreatures(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Query string `json:"query"`
		Limit int    `json:"limit"`
	}
	if err := json.Unmarshal(args, &in); err != nil || strings.TrimSpace(in.Query) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "query required"}
	}
	res, err := s.open5eClient().SearchCreatures(ctx, in.Query, in.Limit)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(res), nil
}

func (s *Server) handleGetCreature(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Key  string `json:"key"`
		Name string `json:"name"`
	}
	if err := json.Unmarshal(args, &in); err != nil {
		return nil, &rpcError{Code: codeInvalidParams, Message: "invalid params"}
	}
	creature, err := s.open5eClient().ResolveCreature(ctx, in.Key, in.Name)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(creature), nil
}

func (s *Server) handleGetCondition(ctx context.Context, args json.RawMessage) (map[string]any, *rpcError) {
	var in struct {
		Name string `json:"name"`
		Key  string `json:"key"`
	}
	if err := json.Unmarshal(args, &in); err != nil {
		return nil, &rpcError{Code: codeInvalidParams, Message: "invalid params"}
	}
	if strings.TrimSpace(in.Key) == "" && strings.TrimSpace(in.Name) == "" {
		return nil, &rpcError{Code: codeInvalidParams, Message: "name or key required"}
	}
	cond, err := s.open5eClient().GetCondition(ctx, in.Name, in.Key)
	if err != nil {
		return toolResultError(err.Error()), nil
	}
	return toolResultText(cond), nil
}
