package open5e

import "testing"

func TestSRDDocumentsDefault(t *testing.T) {
	t.Setenv("WORLDKEEP_SRD_VERSION", "")
	if got := SRDDocuments(); len(got) != 1 || got[0] != "srd-2014" {
		t.Fatalf("default: %v", got)
	}
}

func TestSRDDocumentsBoth(t *testing.T) {
	t.Setenv("WORLDKEEP_SRD_VERSION", "both")
	got := SRDDocuments()
	if len(got) != 2 || got[0] != "srd-2014" || got[1] != "srd-2024" {
		t.Fatalf("both: %v", got)
	}
}
