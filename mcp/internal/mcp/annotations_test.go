package mcp

import "testing"

func TestAllToolsHaveChatGPTAnnotations(t *testing.T) {
	for _, def := range toolDefs() {
		name, _ := def["name"].(string)
		ann, ok := def["annotations"].(map[string]any)
		if !ok {
			t.Fatalf("tool %q missing annotations", name)
		}
		for _, key := range []string{"readOnlyHint", "destructiveHint", "openWorldHint"} {
			if _, ok := ann[key]; !ok {
				t.Fatalf("tool %q missing annotation %q", name, key)
			}
		}
		readOnly, _ := ann["readOnlyHint"].(bool)
		destructive, _ := ann["destructiveHint"].(bool)
		if readOnly && destructive {
			t.Fatalf("tool %q cannot be both readOnly and destructive", name)
		}
	}
}

func TestDestructiveToolsAnnotated(t *testing.T) {
	destructive := map[string]bool{
		"commit_world_update": true,
		"reject_world_update": true,
	}
	for _, def := range toolDefs() {
		name, _ := def["name"].(string)
		ann := def["annotations"].(map[string]any)
		want := destructive[name]
		got, _ := ann["destructiveHint"].(bool)
		if got != want {
			t.Fatalf("tool %q destructiveHint = %v, want %v", name, got, want)
		}
	}
}
