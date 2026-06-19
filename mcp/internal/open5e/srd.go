package open5e

import (
	"os"
	"strings"
)

const DefaultBaseURL = "https://api.open5e.com/v2"

// SRDDocuments returns Open5e document keys allowed for rules lookups.
// WORLDKEEP_SRD_VERSION: srd-2014 (default), srd-2024, or both.
func SRDDocuments() []string {
	switch strings.TrimSpace(strings.ToLower(os.Getenv("WORLDKEEP_SRD_VERSION"))) {
	case "srd-2024", "2024":
		return []string{"srd-2024"}
	case "both", "all":
		return []string{"srd-2014", "srd-2024"}
	default:
		return []string{"srd-2014"}
	}
}

func allowsDocument(docKey string, allowed []string) bool {
	for _, k := range allowed {
		if k == docKey {
			return true
		}
	}
	return false
}
