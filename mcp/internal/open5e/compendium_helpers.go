package open5e

import (
	"net/url"
	"strings"
)

func addDocumentFilter(q url.Values, allowed []string) {
	switch len(allowed) {
	case 0:
		return
	case 1:
		q.Set("document__key", allowed[0])
	default:
		q.Set("document__key__in", strings.Join(allowed, ","))
	}
}

func normalizeLimit(limit int) int {
	if limit <= 0 {
		return 10
	}
	if limit > 25 {
		return 25
	}
	return limit
}

func documentKeyFromRaw(doc any) string {
	switch v := doc.(type) {
	case string:
		return v
	case map[string]any:
		if k, ok := v["key"].(string); ok {
			return k
		}
	}
	return ""
}

func nestedName(v any) string {
	m, ok := v.(map[string]any)
	if !ok {
		return ""
	}
	name, _ := m["name"].(string)
	return name
}
