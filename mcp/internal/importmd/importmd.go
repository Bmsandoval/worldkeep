package importmd

import (
	"strings"

	"github.com/Bmsandoval/worldkeep/mcp/internal/store"
)

// Parse extracts coarse entities from Markdown headings (## Name or ## Type: Name).
func Parse(content string) []store.Entity {
	var out []store.Entity
	for _, block := range strings.Split(content, "\n## ") {
		block = strings.TrimSpace(block)
		if block == "" {
			continue
		}
		lines := strings.SplitN(block, "\n", 2)
		title := strings.TrimSpace(lines[0])
		title = strings.TrimPrefix(title, "## ")
		title = strings.TrimSpace(title)
		if title == "" {
			continue
		}
		body := ""
		if len(lines) > 1 {
			body = strings.TrimSpace(lines[1])
		}
		entityType, name := splitHeading(title)
		out = append(out, store.Entity{
			Type:    entityType,
			Name:    name,
			Summary: firstLine(body),
		})
	}
	return out
}

func splitHeading(title string) (entityType, name string) {
	if i := strings.Index(title, ":"); i > 0 {
		return strings.ToLower(strings.TrimSpace(title[:i])), strings.TrimSpace(title[i+1:])
	}
	return "npc", title
}

func firstLine(s string) string {
	if s == "" {
		return ""
	}
	return strings.TrimSpace(strings.SplitN(s, "\n", 2)[0])
}
