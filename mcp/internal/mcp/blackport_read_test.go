package mcp

import (
	"strings"
	"testing"
)

// Covers docs/poc.md §13 demo prompts 1–3 via MCP read handlers (no live LLM).
func TestBlackportReadPathDemoPrompts(t *testing.T) {
	srv := testServer(t)

	prompts := []struct {
		name string
		tool string
		args map[string]any
		want []string
	}{
		{
			name: "prepare session",
			tool: "compile_scene_context",
			args: map[string]any{"prompt": "Prepare for tonight's session. The party is returning to Blackport."},
			want: []string{"Blackport", "Missing Prince", "Finn"},
		},
		{
			name: "visit Finn",
			tool: "compile_scene_context",
			args: map[string]any{"prompt": "The party visits Finn and asks about the Crimson Guild."},
			want: []string{"Finn", "Crimson Guild"},
		},
		{
			name: "campaign overview",
			tool: "get_campaign_overview",
			args: map[string]any{},
			want: []string{"Shadows of Blackport", "Crimson Guild"},
		},
		{
			name: "campaign dashboard",
			tool: "get_campaign_dashboard",
			args: map[string]any{},
			want: []string{"Shadows of Blackport", "Missing Prince", "pending_update_count"},
		},
		{
			name: "who is Finn",
			tool: "get_entity",
			args: map[string]any{"entity_id": "npc_finn"},
			want: []string{"Finn", "Salt Lantern"},
		},
		{
			name: "flanking rule",
			tool: "search_rulings",
			args: map[string]any{"query": "flanking"},
			want: []string{"+3"},
		},
		{
			name: "active plots",
			tool: "get_active_plots",
			args: map[string]any{},
			want: []string{"Missing Prince"},
		},
	}

	for _, tc := range prompts {
		t.Run(tc.name, func(t *testing.T) {
			text := callTool(t, srv, tc.tool, tc.args)
			for _, w := range tc.want {
				if !strings.Contains(text, w) {
					t.Fatalf("expected %q in %s output", w, tc.tool)
				}
			}
		})
	}
}
