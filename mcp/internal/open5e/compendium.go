package open5e

import (
	"context"
	"encoding/json"
	"fmt"
	"net/url"
	"strings"
)

type SpellSummary struct {
	Key      string `json:"key"`
	Name     string `json:"name"`
	Level    int    `json:"level"`
	School   string `json:"school,omitempty"`
	Document string `json:"document"`
	Snippet  string `json:"snippet,omitempty"`
}

type SpellSearchResult struct {
	Query     string         `json:"query"`
	SRDFilter []string       `json:"srd_filter"`
	Count     int            `json:"count"`
	Results   []SpellSummary `json:"results"`
}

type SpellDetail struct {
	Key         string   `json:"key"`
	Name        string   `json:"name"`
	Level       int      `json:"level"`
	School      string   `json:"school,omitempty"`
	CastingTime string   `json:"casting_time,omitempty"`
	Range       string   `json:"range_text,omitempty"`
	Components  []string `json:"components,omitempty"`
	Desc        string   `json:"desc"`
	HigherLevel string   `json:"higher_level,omitempty"`
	Document    string   `json:"document"`
	Classes     []string `json:"classes,omitempty"`
}

type CreatureSummary struct {
	Key      string  `json:"key"`
	Name     string  `json:"name"`
	CR       float64 `json:"challenge_rating,omitempty"`
	Type     string  `json:"type,omitempty"`
	Size     string  `json:"size,omitempty"`
	Document string  `json:"document"`
	Snippet  string  `json:"snippet,omitempty"`
}

type CreatureSearchResult struct {
	Query     string            `json:"query"`
	SRDFilter []string          `json:"srd_filter"`
	Count     int               `json:"count"`
	Results   []CreatureSummary `json:"results"`
}

type CreatureDetail struct {
	Key           string         `json:"key"`
	Name          string         `json:"name"`
	CR            float64        `json:"challenge_rating,omitempty"`
	Type          string         `json:"type,omitempty"`
	Size          string         `json:"size,omitempty"`
	Alignment     string         `json:"alignment,omitempty"`
	ArmorClass    int            `json:"armor_class,omitempty"`
	HitPoints     int            `json:"hit_points,omitempty"`
	HitDice       string         `json:"hit_dice,omitempty"`
	Speed         map[string]any `json:"speed,omitempty"`
	AbilityScores map[string]any `json:"ability_scores,omitempty"`
	Actions       []any          `json:"actions,omitempty"`
	Traits        []any          `json:"traits,omitempty"`
	Document      string         `json:"document"`
}

type ConditionDetail struct {
	Key      string `json:"key"`
	Name     string `json:"name"`
	Desc     string `json:"desc"`
	Document string `json:"document"`
	Source   string `json:"source,omitempty"`
}

func (c *Client) SearchSpells(ctx context.Context, query string, limit int) (SpellSearchResult, error) {
	query = strings.TrimSpace(query)
	if query == "" {
		return SpellSearchResult{}, fmt.Errorf("query required")
	}
	limit = normalizeLimit(limit)
	allowed := c.srdDocs()

	u, err := url.Parse(strings.TrimRight(c.baseURL(), "/") + "/spells/")
	if err != nil {
		return SpellSearchResult{}, err
	}
	q := u.Query()
	q.Set("search", query)
	q.Set("limit", fmt.Sprintf("%d", limit))
	addDocumentFilter(q, allowed)
	u.RawQuery = q.Encode()

	body, err := c.get(ctx, u.String())
	if err != nil {
		return SpellSearchResult{}, err
	}
	defer body.Close()

	var page struct {
		Results []map[string]any `json:"results"`
	}
	if err := json.NewDecoder(body).Decode(&page); err != nil {
		return SpellSearchResult{}, fmt.Errorf("decode spells: %w", err)
	}

	out := make([]SpellSummary, 0, len(page.Results))
	for _, raw := range page.Results {
		doc := documentKeyFromRaw(raw["document"])
		if !allowsDocument(doc, allowed) {
			continue
		}
		level := 0
		if v, ok := raw["level"].(float64); ok {
			level = int(v)
		}
		desc, _ := raw["desc"].(string)
		out = append(out, SpellSummary{
			Key:      stringField(raw, "key"),
			Name:     stringField(raw, "name"),
			Level:    level,
			School:   nestedName(raw["school"]),
			Document: doc,
			Snippet:  truncate(desc, 240),
		})
	}

	return SpellSearchResult{
		Query:     query,
		SRDFilter: allowed,
		Count:     len(out),
		Results:   out,
	}, nil
}

func (c *Client) GetSpell(ctx context.Context, key string) (SpellDetail, error) {
	key = strings.TrimSpace(key)
	if key == "" {
		return SpellDetail{}, fmt.Errorf("key required")
	}

	u := strings.TrimRight(c.baseURL(), "/") + "/spells/" + url.PathEscape(key) + "/"
	body, err := c.get(ctx, u)
	if err != nil {
		return SpellDetail{}, err
	}
	defer body.Close()

	var raw map[string]any
	if err := json.NewDecoder(body).Decode(&raw); err != nil {
		return SpellDetail{}, fmt.Errorf("decode spell: %w", err)
	}
	doc := documentKeyFromRaw(raw["document"])
	if doc == "" {
		return SpellDetail{}, fmt.Errorf("spell not found: %s", key)
	}
	if !allowsDocument(doc, c.srdDocs()) {
		return SpellDetail{}, fmt.Errorf("spell %s is outside configured SRD filter (%v)", key, c.srdDocs())
	}

	level := 0
	if v, ok := raw["level"].(float64); ok {
		level = int(v)
	}
	components := spellComponents(raw)
	classes := []string{}
	if arr, ok := raw["classes"].([]any); ok {
		for _, item := range arr {
			if name := nestedName(item); name != "" {
				classes = append(classes, name)
			}
		}
	}

	return SpellDetail{
		Key:         stringField(raw, "key"),
		Name:        stringField(raw, "name"),
		Level:       level,
		School:      nestedName(raw["school"]),
		CastingTime: stringField(raw, "casting_time"),
		Range:       firstNonEmpty(stringField(raw, "range_text"), stringField(raw, "range")),
		Components:  components,
		Desc:        stringField(raw, "desc"),
		HigherLevel: stringField(raw, "higher_level"),
		Document:    doc,
		Classes:     classes,
	}, nil
}

func (c *Client) ResolveSpell(ctx context.Context, key, name string) (SpellDetail, error) {
	if k := strings.TrimSpace(key); k != "" {
		return c.GetSpell(ctx, k)
	}
	n := strings.TrimSpace(name)
	if n == "" {
		return SpellDetail{}, fmt.Errorf("key or name required")
	}
	res, err := c.SearchSpells(ctx, n, 10)
	if err != nil {
		return SpellDetail{}, err
	}
	want := strings.EqualFold
	for _, hit := range res.Results {
		if want(hit.Name, n) {
			return c.GetSpell(ctx, hit.Key)
		}
	}
	if len(res.Results) == 1 {
		return c.GetSpell(ctx, res.Results[0].Key)
	}
	if len(res.Results) == 0 {
		return SpellDetail{}, fmt.Errorf("spell not found: %s", n)
	}
	return SpellDetail{}, fmt.Errorf("ambiguous spell name %q (%d matches); pass key from search_spells", n, len(res.Results))
}

func (c *Client) SearchCreatures(ctx context.Context, query string, limit int) (CreatureSearchResult, error) {
	query = strings.TrimSpace(query)
	if query == "" {
		return CreatureSearchResult{}, fmt.Errorf("query required")
	}
	limit = normalizeLimit(limit)
	allowed := c.srdDocs()

	u, err := url.Parse(strings.TrimRight(c.baseURL(), "/") + "/creatures/")
	if err != nil {
		return CreatureSearchResult{}, err
	}
	q := u.Query()
	q.Set("search", query)
	q.Set("limit", fmt.Sprintf("%d", limit))
	addDocumentFilter(q, allowed)
	u.RawQuery = q.Encode()

	body, err := c.get(ctx, u.String())
	if err != nil {
		return CreatureSearchResult{}, err
	}
	defer body.Close()

	var page struct {
		Results []map[string]any `json:"results"`
	}
	if err := json.NewDecoder(body).Decode(&page); err != nil {
		return CreatureSearchResult{}, fmt.Errorf("decode creatures: %w", err)
	}

	out := make([]CreatureSummary, 0, len(page.Results))
	for _, raw := range page.Results {
		doc := documentKeyFromRaw(raw["document"])
		if !allowsDocument(doc, allowed) {
			continue
		}
		cr, _ := raw["challenge_rating"].(float64)
		out = append(out, CreatureSummary{
			Key:      stringField(raw, "key"),
			Name:     stringField(raw, "name"),
			CR:       cr,
			Type:     nestedName(raw["type"]),
			Size:     nestedName(raw["size"]),
			Document: doc,
			Snippet:  truncate(creatureSnippet(raw), 240),
		})
	}

	return CreatureSearchResult{
		Query:     query,
		SRDFilter: allowed,
		Count:     len(out),
		Results:   out,
	}, nil
}

func (c *Client) GetCreature(ctx context.Context, key string) (CreatureDetail, error) {
	key = strings.TrimSpace(key)
	if key == "" {
		return CreatureDetail{}, fmt.Errorf("key required")
	}

	u := strings.TrimRight(c.baseURL(), "/") + "/creatures/" + url.PathEscape(key) + "/"
	body, err := c.get(ctx, u)
	if err != nil {
		return CreatureDetail{}, err
	}
	defer body.Close()

	var raw map[string]any
	if err := json.NewDecoder(body).Decode(&raw); err != nil {
		return CreatureDetail{}, fmt.Errorf("decode creature: %w", err)
	}
	doc := documentKeyFromRaw(raw["document"])
	if doc == "" {
		return CreatureDetail{}, fmt.Errorf("creature not found: %s", key)
	}
	if !allowsDocument(doc, c.srdDocs()) {
		return CreatureDetail{}, fmt.Errorf("creature %s is outside configured SRD filter (%v)", key, c.srdDocs())
	}

	cr, _ := raw["challenge_rating"].(float64)
	ac := 0
	if v, ok := raw["armor_class"].(float64); ok {
		ac = int(v)
	}
	hp := 0
	if v, ok := raw["hit_points"].(float64); ok {
		hp = int(v)
	}
	speed, _ := raw["speed"].(map[string]any)
	abilityScores, _ := raw["ability_scores"].(map[string]any)
	actions, _ := raw["actions"].([]any)
	traits, _ := raw["traits"].([]any)

	return CreatureDetail{
		Key:           stringField(raw, "key"),
		Name:          stringField(raw, "name"),
		CR:            cr,
		Type:          nestedName(raw["type"]),
		Size:          nestedName(raw["size"]),
		Alignment:     stringField(raw, "alignment"),
		ArmorClass:    ac,
		HitPoints:     hp,
		HitDice:       stringField(raw, "hit_dice"),
		Speed:         speed,
		AbilityScores: abilityScores,
		Actions:       actions,
		Traits:        traits,
		Document:      doc,
	}, nil
}

func (c *Client) ResolveCreature(ctx context.Context, key, name string) (CreatureDetail, error) {
	if k := strings.TrimSpace(key); k != "" {
		return c.GetCreature(ctx, k)
	}
	n := strings.TrimSpace(name)
	if n == "" {
		return CreatureDetail{}, fmt.Errorf("key or name required")
	}
	res, err := c.SearchCreatures(ctx, n, 10)
	if err != nil {
		return CreatureDetail{}, err
	}
	for _, hit := range res.Results {
		if strings.EqualFold(hit.Name, n) {
			return c.GetCreature(ctx, hit.Key)
		}
	}
	if len(res.Results) == 1 {
		return c.GetCreature(ctx, res.Results[0].Key)
	}
	if len(res.Results) == 0 {
		return CreatureDetail{}, fmt.Errorf("creature not found: %s", n)
	}
	return CreatureDetail{}, fmt.Errorf("ambiguous creature name %q (%d matches); pass key from search_creatures", n, len(res.Results))
}

func (c *Client) GetCondition(ctx context.Context, name, key string) (ConditionDetail, error) {
	if k := strings.TrimSpace(key); k != "" {
		return c.fetchConditionByKey(ctx, k)
	}
	n := strings.TrimSpace(name)
	if n == "" {
		return ConditionDetail{}, fmt.Errorf("name or key required")
	}

	if cond, err := c.fetchConditionByKey(ctx, slugConditionKey(n)); err == nil {
		return cond, nil
	}

	allowed := c.srdDocs()
	u, err := url.Parse(strings.TrimRight(c.baseURL(), "/") + "/conditions/")
	if err != nil {
		return ConditionDetail{}, err
	}
	q := u.Query()
	q.Set("search", n)
	q.Set("limit", "25")
	addDocumentFilter(q, allowed)
	u.RawQuery = q.Encode()

	body, err := c.get(ctx, u.String())
	if err == nil {
		defer body.Close()
		var page struct {
			Results []map[string]any `json:"results"`
		}
		if json.NewDecoder(body).Decode(&page) == nil {
			for _, raw := range page.Results {
				if !strings.EqualFold(stringField(raw, "name"), n) {
					continue
				}
				doc := documentKeyFromRaw(raw["document"])
				if allowsDocument(doc, allowed) {
					return mapCondition(raw, "open5e_conditions"), nil
				}
			}
		}
	}

	// Open5e SRD often lacks standalone condition records; fall back to global search.
	searchURL, err := url.Parse(strings.TrimRight(c.baseURL(), "/") + "/search/")
	if err != nil {
		return ConditionDetail{}, err
	}
	sq := searchURL.Query()
	sq.Set("query", n)
	sq.Set("limit", "20")
	searchURL.RawQuery = sq.Encode()

	body, err = c.get(ctx, searchURL.String())
	if err != nil {
		return ConditionDetail{}, err
	}
	defer body.Close()

	var page paginatedSearch
	if err := json.NewDecoder(body).Decode(&page); err != nil {
		return ConditionDetail{}, fmt.Errorf("decode condition search: %w", err)
	}
	for _, hit := range page.Results {
		if !allowsDocument(hit.Document.Key, allowed) {
			continue
		}
		if hit.ObjectModel != "Rule" && hit.ObjectModel != "Condition" {
			continue
		}
		if !strings.EqualFold(hit.ObjectName, n) && !strings.EqualFold(hit.ObjectName, titleCase(n)) {
			continue
		}
		if hit.ObjectModel == "Rule" && hit.ObjectPK != "" {
			rule, err := c.GetRulesSection(ctx, hit.ObjectPK)
			if err == nil {
				return ConditionDetail{
					Key:      rule.Key,
					Name:     rule.Name,
					Desc:     rule.Desc,
					Document: rule.Document,
					Source:   "open5e_rules",
				}, nil
			}
		}
		return ConditionDetail{
			Key:      hit.ObjectPK,
			Name:     hit.ObjectName,
			Desc:     hit.Text,
			Document: hit.Document.Key,
			Source:   "open5e_search",
		}, nil
	}

	return ConditionDetail{}, fmt.Errorf("condition not found in SRD filter (%v): %s", allowed, n)
}

func (c *Client) fetchConditionByKey(ctx context.Context, key string) (ConditionDetail, error) {
	u := strings.TrimRight(c.baseURL(), "/") + "/conditions/" + url.PathEscape(key) + "/"
	body, err := c.get(ctx, u)
	if err != nil {
		return ConditionDetail{}, err
	}
	defer body.Close()

	var raw map[string]any
	if err := json.NewDecoder(body).Decode(&raw); err != nil {
		return ConditionDetail{}, fmt.Errorf("decode condition: %w", err)
	}
	if stringField(raw, "key") == "" {
		return ConditionDetail{}, fmt.Errorf("condition not found: %s", key)
	}
	doc := documentKeyFromRaw(raw["document"])
	if doc != "" && !allowsDocument(doc, c.srdDocs()) {
		return ConditionDetail{}, fmt.Errorf("condition %s is outside configured SRD filter (%v)", key, c.srdDocs())
	}
	return mapCondition(raw, "open5e_conditions"), nil
}

func mapCondition(raw map[string]any, source string) ConditionDetail {
	return ConditionDetail{
		Key:      stringField(raw, "key"),
		Name:     stringField(raw, "name"),
		Desc:     stringField(raw, "desc"),
		Document: documentKeyFromRaw(raw["document"]),
		Source:   source,
	}
}

func stringField(raw map[string]any, key string) string {
	v, _ := raw[key].(string)
	return v
}

func firstNonEmpty(values ...string) string {
	for _, v := range values {
		if strings.TrimSpace(v) != "" {
			return v
		}
	}
	return ""
}

func spellComponents(raw map[string]any) []string {
	var out []string
	if truthy(raw["verbal"]) {
		out = append(out, "V")
	}
	if truthy(raw["somatic"]) {
		out = append(out, "S")
	}
	if truthy(raw["material"]) {
		out = append(out, "M")
	}
	return out
}

func truthy(v any) bool {
	b, ok := v.(bool)
	return ok && b
}

func creatureSnippet(raw map[string]any) string {
	parts := []string{
		fmt.Sprintf("CR %v", raw["challenge_rating"]),
		nestedName(raw["type"]),
		nestedName(raw["size"]),
	}
	if ac, ok := raw["armor_class"].(float64); ok {
		parts = append(parts, fmt.Sprintf("AC %d", int(ac)))
	}
	if hp, ok := raw["hit_points"].(float64); ok {
		parts = append(parts, fmt.Sprintf("HP %d", int(hp)))
	}
	return strings.Join(parts, " · ")
}

func slugConditionKey(name string) string {
	return strings.ToLower(strings.TrimSpace(name))
}

func titleCase(s string) string {
	s = strings.TrimSpace(s)
	if s == "" {
		return s
	}
	return strings.ToUpper(s[:1]) + strings.ToLower(s[1:])
}
