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
