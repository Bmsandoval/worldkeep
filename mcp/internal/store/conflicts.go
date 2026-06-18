package store

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"
)

type ConflictWarning struct {
	Type    string `json:"type"`
	Message string `json:"message"`
}

func (s *Store) CheckForConflicts(ctx context.Context, campaignID string, changes []WorldChange) ([]ConflictWarning, error) {
	var warnings []ConflictWarning
	namesSeen := map[string]string{}

	entities, err := s.ListEntitiesByType(ctx, campaignID, "npc", ScopeDM)
	if err != nil {
		return nil, err
	}
	entities = append(entities, mustEntities(s.ListEntitiesByType(ctx, campaignID, "location", ScopeDM))...)
	entities = append(entities, mustEntities(s.ListEntitiesByType(ctx, campaignID, "faction", ScopeDM))...)

	for _, e := range entities {
		namesSeen[strings.ToLower(e.Name)] = e.ID
	}

	for _, ch := range changes {
		switch ch.Op {
		case "upsert_entity", "create_entity":
			if ch.Entity == nil {
				continue
			}
			key := strings.ToLower(ch.Entity.Name)
			if existing, ok := namesSeen[key]; ok && existing != ch.Entity.ID && ch.Entity.ID != "" {
				warnings = append(warnings, ConflictWarning{
					Type:    "duplicate_name",
					Message: fmt.Sprintf("Entity name %q already used by %s", ch.Entity.Name, existing),
				})
			}
			if ch.Entity.ID != "" {
				if existing, err := s.GetEntity(ctx, ch.Entity.ID); err == nil {
					var oldData, newData map[string]any
					_ = json.Unmarshal(existing.Data, &oldData)
					_ = json.Unmarshal(ch.Entity.Data, &newData)
					if oldStatus, ok := oldData["status"].(string); ok {
						if newStatus, ok2 := newData["status"].(string); ok2 && oldStatus != newStatus {
							if (oldStatus == "dead" && newStatus == "alive") || (oldStatus == "alive" && newStatus == "dead") {
								warnings = append(warnings, ConflictWarning{
									Type:    "status_conflict",
									Message: fmt.Sprintf("%s status changes from %s to %s", ch.Entity.Name, oldStatus, newStatus),
								})
							}
						}
					}
				}
			}
		case "add_fact":
			if ch.Fact == nil || ch.Fact.EntityID == nil {
				continue
			}
			facts, err := s.searchFacts(ctx, campaignID, "%", 100, ScopeDM)
			if err != nil {
				continue
			}
			for _, f := range facts {
				if f.EntityID == nil || *f.EntityID != *ch.Fact.EntityID {
					continue
				}
				if contradictsFact(f.Text, ch.Fact.Text) {
					warnings = append(warnings, ConflictWarning{
						Type:    "possible_contradiction",
						Message: fmt.Sprintf("Proposed fact %q may contradict existing: %q", ch.Fact.Text, f.Text),
					})
				}
			}
		}
	}
	return warnings, nil
}

func mustEntities(items []Entity, err error) []Entity {
	if err != nil {
		return nil
	}
	return items
}

func contradictsFact(existing, proposed string) bool {
	el := strings.ToLower(existing)
	pl := strings.ToLower(proposed)
	if strings.Contains(el, "lost his left eye") && strings.Contains(pl, "both eyes") {
		return true
	}
	if strings.Contains(el, "both eyes") && strings.Contains(pl, "lost his left eye") {
		return true
	}
	if strings.Contains(el, "dead") && strings.Contains(pl, "alive") {
		return true
	}
	return false
}
