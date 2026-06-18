package mcp

import (
	"encoding/json"
	"testing"
)

func TestInitializeReturnsInstructions(t *testing.T) {
	s := &Server{CampaignID: "campaign_001"}
	params, _ := json.Marshal(map[string]any{"protocolVersion": "2025-06-18"})
	resp, ok := s.Dispatch(rpcRequest{
		JSONRPC: "2.0",
		ID:      json.RawMessage(`1`),
		Method:  "initialize",
		Params:  params,
	})
	if !ok {
		t.Fatal("expected response")
	}
	if resp.Error != nil {
		t.Fatalf("error: %v", resp.Error)
	}
	result, ok := resp.Result.(map[string]any)
	if !ok {
		t.Fatalf("result type %T", resp.Result)
	}
	if result["instructions"] != ServerInstructions {
		t.Fatal("missing instructions")
	}
}

func TestToolsListCatalog(t *testing.T) {
	s := &Server{CampaignID: "campaign_001"}
	resp, ok := s.Dispatch(rpcRequest{
		JSONRPC: "2.0",
		ID:      json.RawMessage(`2`),
		Method:  "tools/list",
	})
	if !ok || resp.Error != nil {
		t.Fatalf("tools/list failed: %+v", resp)
	}
	result := resp.Result.(map[string]any)
	tools := result["tools"].([]map[string]any)
	if len(tools) < 10 {
		t.Fatalf("expected full catalog, got %d tools", len(tools))
	}
}
