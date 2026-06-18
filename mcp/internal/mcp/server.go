package mcp

import (
	"bufio"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"os"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

type Server struct {
	Store      *store.Store
	CampaignID string
}

func (s *Server) Dispatch(req rpcRequest) (rpcResponse, bool) {
	isNotification := len(req.ID) == 0
	reply := func(result any, rerr *rpcError) (rpcResponse, bool) {
		if isNotification {
			return rpcResponse{}, false
		}
		return rpcResponse{JSONRPC: "2.0", ID: req.ID, Result: result, Error: rerr}, true
	}

	switch req.Method {
	case "initialize":
		return reply(s.initializeResult(req.Params), nil)
	case "notifications/initialized", "notifications/cancelled":
		return rpcResponse{}, false
	case "ping":
		return reply(map[string]any{}, nil)
	case "tools/list":
		return reply(map[string]any{"tools": toolDefs()}, nil)
	case "tools/call":
		var p struct {
			Name      string          `json:"name"`
			Arguments json.RawMessage `json:"arguments"`
		}
		if err := json.Unmarshal(req.Params, &p); err != nil {
			return reply(nil, &rpcError{Code: codeInvalidParams, Message: "invalid params"})
		}
		result, rerr := s.callTool(context.Background(), p.Name, p.Arguments)
		if rerr != nil {
			return reply(nil, rerr)
		}
		return reply(result, nil)
	default:
		return reply(nil, &rpcError{Code: codeMethodNotFound, Message: "method not found: " + req.Method})
	}
}

func (s *Server) initializeResult(params json.RawMessage) map[string]any {
	version := defaultProtocolVersion
	if len(params) > 0 {
		var p struct {
			ProtocolVersion string `json:"protocolVersion"`
		}
		if json.Unmarshal(params, &p) == nil && p.ProtocolVersion != "" {
			version = p.ProtocolVersion
		}
	}
	return map[string]any{
		"protocolVersion": version,
		"capabilities": map[string]any{
			"tools": map[string]any{},
		},
		"serverInfo": map[string]any{
			"name":    "worldkeep",
			"version": "0.1.0",
		},
		"instructions": ServerInstructions,
	}
}

func (s *Server) callTool(ctx context.Context, name string, args json.RawMessage) (map[string]any, *rpcError) {
	switch name {
	case "get_campaign_overview":
		return s.handleGetCampaignOverview(ctx, args)
	case "get_entity":
		return s.handleGetEntity(ctx, args)
	case "search_world":
		return s.handleSearchWorld(ctx, args)
	case "compile_scene_context":
		return s.handleCompileSceneContext(ctx, args)
	case "get_recent_events":
		return s.handleGetRecentEvents(ctx, args)
	case "get_active_plots":
		return s.handleGetActivePlots(ctx, args)
	case "search_rulings":
		return s.handleSearchRulings(ctx, args)
	default:
		return toolResultError("tool not implemented yet: " + name), nil
	}
}

func (s *Server) RunStdio(in io.Reader, out io.Writer) error {
	sc := bufio.NewScanner(in)
	sc.Buffer(make([]byte, 0, 1024*1024), 1024*1024)
	enc := json.NewEncoder(out)
	for sc.Scan() {
		line := sc.Bytes()
		if len(line) == 0 {
			continue
		}
		var req rpcRequest
		if err := json.Unmarshal(line, &req); err != nil {
			_ = enc.Encode(rpcResponse{
				JSONRPC: "2.0",
				Error:   &rpcError{Code: codeParseError, Message: "parse error"},
			})
			continue
		}
		resp, ok := s.Dispatch(req)
		if !ok {
			continue
		}
		if err := enc.Encode(resp); err != nil {
			return fmt.Errorf("encode response: %w", err)
		}
	}
	return sc.Err()
}

func (s *Server) RunStdioOS() error {
	return s.RunStdio(os.Stdin, os.Stdout)
}
