package mcp

import (
	"strings"
	"testing"
)

func TestStartEndSessionMCP(t *testing.T) {
	srv := testServer(t)
	startText := callTool(t, srv, "start_session", map[string]any{"title": "Tonight"})
	if !strings.Contains(startText, "session_") {
		t.Fatalf("expected session id: %s", startText)
	}
	endText := callTool(t, srv, "end_session", map[string]any{})
	if !strings.Contains(endText, "closed") {
		t.Fatalf("expected closed session: %s", endText)
	}
}
