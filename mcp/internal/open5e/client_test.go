package open5e

import (
	"context"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestSearchRulesReferenceFiltersSRD(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/v2/search/" {
			t.Fatalf("unexpected path %s", r.URL.Path)
		}
		_, _ = w.Write([]byte(`{
			"count": 2,
			"results": [
				{
					"document": {"key": "srd-2014", "name": "SRD 5.1"},
					"object_pk": "srd_monsters_grapple-rules",
					"object_name": "Grapple Rules for Monsters",
					"object_model": "Rule",
					"text": "Grapple rules text.",
					"match_score": 1.0
				},
				{
					"document": {"key": "tob2", "name": "Tome of Beasts 2"},
					"object_pk": "tob2_foo",
					"object_name": "Third Party",
					"object_model": "Creature",
					"text": "Not SRD.",
					"match_score": 0.9
				}
			]
		}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	res, err := c.SearchRulesReference(context.Background(), "grapple", 10)
	if err != nil {
		t.Fatal(err)
	}
	if res.Count != 1 || len(res.Results) != 1 {
		t.Fatalf("expected 1 hit, got %+v", res)
	}
	if res.Results[0].RuleKey != "srd_monsters_grapple-rules" {
		t.Fatalf("unexpected rule key: %q", res.Results[0].RuleKey)
	}
}

func TestGetRulesSection(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/v2/rules/srd_monsters_grapple-rules/" {
			t.Fatalf("unexpected path %s", r.URL.Path)
		}
		_, _ = w.Write([]byte(`{
			"key": "srd_monsters_grapple-rules",
			"name": "Grapple Rules for Monsters",
			"desc": "Full grapple text.",
			"document": "srd-2014",
			"ruleset": "srd_monsters"
		}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	rule, err := c.GetRulesSection(context.Background(), "srd_monsters_grapple-rules")
	if err != nil {
		t.Fatal(err)
	}
	if rule.Name != "Grapple Rules for Monsters" || rule.Desc != "Full grapple text." {
		t.Fatalf("unexpected rule: %+v", rule)
	}
}

func TestGetRulesSectionRejectsNonSRDDocument(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte(`{
			"key": "tob2_foo",
			"name": "Third Party",
			"desc": "text",
			"document": "tob2"
		}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	_, err := c.GetRulesSection(context.Background(), "tob2_foo")
	if err == nil {
		t.Fatal("expected SRD filter error")
	}
}
