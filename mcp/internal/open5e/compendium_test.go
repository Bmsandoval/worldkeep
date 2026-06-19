package open5e

import (
	"context"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestSearchSpells(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/v2/spells/" {
			t.Fatalf("path %s", r.URL.Path)
		}
		if r.URL.Query().Get("document__key") != "srd-2014" {
			t.Fatalf("document filter: %s", r.URL.Query().Get("document__key"))
		}
		_, _ = w.Write([]byte(`{"results":[{
			"key":"srd_fireball","name":"Fireball","level":3,
			"document":{"key":"srd-2014"},"school":{"name":"Evocation"},
			"desc":"A bright streak flashes."
		}]}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	res, err := c.SearchSpells(context.Background(), "fireball", 5)
	if err != nil {
		t.Fatal(err)
	}
	if res.Count != 1 || res.Results[0].Key != "srd_fireball" {
		t.Fatalf("unexpected: %+v", res)
	}
}

func TestGetSpell(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/v2/spells/srd_fireball/" {
			t.Fatalf("path %s", r.URL.Path)
		}
		_, _ = w.Write([]byte(`{
			"key":"srd_fireball","name":"Fireball","level":3,
			"document":{"key":"srd-2014"},"school":{"name":"Evocation"},
			"casting_time":"1 action","range_text":"150 feet",
			"verbal":true,"somatic":true,"material":true,
			"desc":"Boom."
		}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	spell, err := c.GetSpell(context.Background(), "srd_fireball")
	if err != nil {
		t.Fatal(err)
	}
	if spell.Name != "Fireball" || !strings.Contains(strings.Join(spell.Components, ""), "V") {
		t.Fatalf("unexpected spell: %+v", spell)
	}
}

func TestSearchCreatures(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte(`{"results":[{
			"key":"srd_goblin","name":"Goblin","challenge_rating":0.25,
			"document":{"key":"srd-2014"},"type":{"name":"Humanoid"},"size":{"name":"Small"},
			"armor_class":15,"hit_points":7
		}]}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	res, err := c.SearchCreatures(context.Background(), "goblin", 5)
	if err != nil || res.Results[0].Key != "srd_goblin" {
		t.Fatalf("search creatures: %+v err=%v", res, err)
	}
}

func TestGetCreature(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		_, _ = w.Write([]byte(`{
			"key":"srd_goblin","name":"Goblin","challenge_rating":0.25,
			"document":{"key":"srd-2014"},"type":{"name":"Humanoid"},"size":{"name":"Small"},
			"armor_class":15,"hit_points":7,"hit_dice":"2d6",
			"actions":[{"name":"Scimitar","desc":"Melee attack."}]
		}`))
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	creature, err := c.GetCreature(context.Background(), "srd_goblin")
	if err != nil || creature.ArmorClass != 15 || len(creature.Actions) != 1 {
		t.Fatalf("creature: %+v err=%v", creature, err)
	}
}

func TestGetConditionFromRulesFallback(t *testing.T) {
	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		switch {
		case r.URL.Path == "/v2/conditions/grappled/":
			w.WriteHeader(http.StatusNotFound)
			_, _ = w.Write([]byte(`{"detail":"No Condition matches the given query."}`))
		case r.URL.Path == "/v2/conditions/":
			_, _ = w.Write([]byte(`{"results":[]}`))
		case r.URL.Path == "/v2/search/":
			_, _ = w.Write([]byte(`{"results":[{
				"document":{"key":"srd-2014","name":"SRD"},
				"object_pk":"srd_conditions_grappled",
				"object_name":"Grappled",
				"object_model":"Rule",
				"text":"snippet"
			}]}`))
		case r.URL.Path == "/v2/rules/srd_conditions_grappled/":
			_, _ = w.Write([]byte(`{
				"key":"srd_conditions_grappled","name":"Grappled",
				"desc":"A grappled creature's speed becomes 0.",
				"document":"srd-2014"
			}`))
		default:
			t.Fatalf("unexpected %s", r.URL.Path)
		}
	}))
	defer srv.Close()

	c := &Client{BaseURL: srv.URL + "/v2", HTTP: srv.Client(), SRDDocKeys: []string{"srd-2014"}}
	cond, err := c.GetCondition(context.Background(), "Grappled", "")
	if err != nil {
		t.Fatal(err)
	}
	if cond.Name != "Grappled" || cond.Source != "open5e_rules" {
		t.Fatalf("unexpected condition: %+v", cond)
	}
}
