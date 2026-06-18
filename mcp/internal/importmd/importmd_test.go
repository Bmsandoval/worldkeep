package importmd

import "testing"

func TestParseMarkdownHeadings(t *testing.T) {
	md := "## NPC: Finn\nOne-eyed bartender.\n\n## location: Blackport\nFoggy port city.\n"
	entities := Parse(md)
	if len(entities) != 2 {
		t.Fatalf("expected 2 entities, got %d", len(entities))
	}
	if entities[0].Type != "npc" || entities[0].Name != "Finn" {
		t.Fatalf("unexpected first entity: %+v", entities[0])
	}
}
