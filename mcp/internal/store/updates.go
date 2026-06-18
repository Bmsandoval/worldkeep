package store

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
)

type PendingUpdate struct {
	ID              string          `json:"id"`
	CampaignID      string          `json:"campaign_id"`
	ProposedChanges json.RawMessage `json:"proposed_changes"`
	Reason          string          `json:"reason"`
	Status          string          `json:"status"`
	CreatedAt       string          `json:"created_at"`
}

type WorldChange struct {
	Op       string          `json:"op"`
	Entity   *Entity         `json:"entity,omitempty"`
	EntityID string          `json:"entity_id,omitempty"`
	Patch    json.RawMessage `json:"patch,omitempty"`
	Fact     *Fact           `json:"fact,omitempty"`
	Event    *Event          `json:"event,omitempty"`
}

func (s *Store) ProposeWorldUpdate(ctx context.Context, campaignID string, changes []WorldChange, reason string) (PendingUpdate, error) {
	raw, err := json.Marshal(changes)
	if err != nil {
		return PendingUpdate{}, fmt.Errorf("marshal changes: %w", err)
	}
	id := "update_" + uuid.NewString()[:8]
	_, err = s.db.ExecContext(ctx, `
INSERT INTO pending_updates (id, campaign_id, proposed_changes, reason, status, created_at)
VALUES (?, ?, ?, ?, 'pending', datetime('now'))`,
		id, campaignID, string(raw), reason,
	)
	if err != nil {
		return PendingUpdate{}, fmt.Errorf("propose update: %w", err)
	}
	return s.GetPendingUpdate(ctx, id)
}

func (s *Store) GetPendingUpdate(ctx context.Context, id string) (PendingUpdate, error) {
	var u PendingUpdate
	var changes string
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, proposed_changes, reason, status, created_at
FROM pending_updates WHERE id = ?`, id,
	).Scan(&u.ID, &u.CampaignID, &changes, &u.Reason, &u.Status, &u.CreatedAt)
	if err != nil {
		return PendingUpdate{}, fmt.Errorf("get pending update: %w", err)
	}
	u.ProposedChanges = json.RawMessage(changes)
	return u, nil
}

func (s *Store) ListPendingUpdates(ctx context.Context, campaignID string) ([]PendingUpdate, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, proposed_changes, reason, status, created_at
FROM pending_updates
WHERE campaign_id = ? AND status = 'pending'
ORDER BY created_at DESC`, campaignID)
	if err != nil {
		return nil, fmt.Errorf("list pending updates: %w", err)
	}
	defer rows.Close()

	var out []PendingUpdate
	for rows.Next() {
		var u PendingUpdate
		var changes string
		if err := rows.Scan(&u.ID, &u.CampaignID, &changes, &u.Reason, &u.Status, &u.CreatedAt); err != nil {
			return nil, fmt.Errorf("scan pending update: %w", err)
		}
		u.ProposedChanges = json.RawMessage(changes)
		out = append(out, u)
	}
	return out, rows.Err()
}

func (s *Store) RejectWorldUpdate(ctx context.Context, id, reason string) error {
	res, err := s.db.ExecContext(ctx, `
UPDATE pending_updates SET status = 'rejected', reason = COALESCE(NULLIF(?, ''), reason)
WHERE id = ? AND status = 'pending'`, reason, id)
	if err != nil {
		return fmt.Errorf("reject update: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return fmt.Errorf("pending update not found: %s", id)
	}
	return nil
}

func (s *Store) CommitWorldUpdate(ctx context.Context, id string) (PendingUpdate, error) {
	u, err := s.GetPendingUpdate(ctx, id)
	if err != nil {
		return PendingUpdate{}, err
	}
	if u.Status != "pending" {
		return PendingUpdate{}, fmt.Errorf("update is not pending: %s", u.Status)
	}

	var changes []WorldChange
	if err := json.Unmarshal(u.ProposedChanges, &changes); err != nil {
		return PendingUpdate{}, fmt.Errorf("parse changes: %w", err)
	}
	for _, ch := range changes {
		if err := s.applyChange(ctx, u.CampaignID, ch); err != nil {
			return PendingUpdate{}, err
		}
	}

	_, err = s.db.ExecContext(ctx, `UPDATE pending_updates SET status = 'committed' WHERE id = ?`, id)
	if err != nil {
		return PendingUpdate{}, fmt.Errorf("mark committed: %w", err)
	}
	return s.GetPendingUpdate(ctx, id)
}

func (s *Store) applyChange(ctx context.Context, campaignID string, ch WorldChange) error {
	switch ch.Op {
	case "upsert_entity", "create_entity":
		if ch.Entity == nil {
			return fmt.Errorf("entity required for %s", ch.Op)
		}
		if ch.Entity.CampaignID == "" {
			ch.Entity.CampaignID = campaignID
		}
		return s.UpsertEntity(ctx, *ch.Entity)
	case "update_entity":
		if ch.EntityID == "" || len(ch.Patch) == 0 {
			return fmt.Errorf("entity_id and patch required for update_entity")
		}
		return s.PatchEntity(ctx, ch.EntityID, ch.Patch)
	case "add_fact":
		if ch.Fact == nil {
			return fmt.Errorf("fact required for add_fact")
		}
		if ch.Fact.CampaignID == "" {
			ch.Fact.CampaignID = campaignID
		}
		if ch.Fact.ID == "" {
			ch.Fact.ID = "fact_" + uuid.NewString()[:8]
		}
		return s.AddFact(ctx, *ch.Fact)
	case "record_event":
		if ch.Event == nil {
			return fmt.Errorf("event required for record_event")
		}
		if ch.Event.CampaignID == "" {
			ch.Event.CampaignID = campaignID
		}
		if ch.Event.ID == "" {
			ch.Event.ID = "event_" + uuid.NewString()[:8]
		}
		return s.AddEvent(ctx, *ch.Event)
	default:
		return fmt.Errorf("unknown change op: %s", ch.Op)
	}
}

func (s *Store) PatchEntity(ctx context.Context, id string, patch json.RawMessage) error {
	entity, err := s.GetEntity(ctx, id)
	if err != nil {
		return err
	}
	var fields map[string]json.RawMessage
	if err := json.Unmarshal(patch, &fields); err != nil {
		return fmt.Errorf("patch json: %w", err)
	}
	if v, ok := fields["name"]; ok {
		_ = json.Unmarshal(v, &entity.Name)
	}
	if v, ok := fields["summary"]; ok {
		_ = json.Unmarshal(v, &entity.Summary)
	}
	if v, ok := fields["data"]; ok {
		entity.Data = v
	}
	entity.UpdatedAt = time.Now().UTC().Format(time.RFC3339)
	return s.UpsertEntity(ctx, entity)
}
