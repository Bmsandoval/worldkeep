package store

import (
	"context"
	"strings"
)

// SearchWorldHybrid expands the query into tokens and merges SQL LIKE results.
// Embedding search is not wired yet; token expansion is the MVP stub.
func (s *Store) SearchWorldHybrid(ctx context.Context, campaignID, query string, limit int, scope ReadScope) ([]Entity, []Fact, error) {
	if limit <= 0 {
		limit = 20
	}
	tokens := tokenizeSearch(query)
	if len(tokens) == 0 {
		return s.SearchWorld(ctx, campaignID, query, limit, scope)
	}

	seenEntities := map[string]Entity{}
	seenFacts := map[string]Fact{}
	for _, tok := range tokens {
		entities, facts, err := s.SearchWorld(ctx, campaignID, tok, limit, scope)
		if err != nil {
			return nil, nil, err
		}
		for _, e := range entities {
			seenEntities[e.ID] = e
		}
		for _, f := range facts {
			seenFacts[f.ID] = f
		}
	}

	entities := make([]Entity, 0, len(seenEntities))
	for _, e := range seenEntities {
		entities = append(entities, e)
		if len(entities) >= limit {
			break
		}
	}
	facts := make([]Fact, 0, len(seenFacts))
	for _, f := range seenFacts {
		facts = append(facts, f)
		if len(facts) >= limit {
			break
		}
	}
	return entities, facts, nil
}

func tokenizeSearch(query string) []string {
	words := strings.Fields(strings.ToLower(query))
	var tokens []string
	seen := map[string]bool{}
	for _, w := range words {
		w = strings.Trim(w, ".,!?\"'")
		if len(w) < 3 || seen[w] {
			continue
		}
		seen[w] = true
		tokens = append(tokens, w)
	}
	return tokens
}
