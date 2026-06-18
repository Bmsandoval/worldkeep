package store

import (
	"context"
	"fmt"

	"github.com/google/uuid"
)

type Session struct {
	ID         string  `json:"id"`
	CampaignID string  `json:"campaign_id"`
	Title      string  `json:"title"`
	Status     string  `json:"status"`
	StartedAt  string  `json:"started_at"`
	EndedAt    *string `json:"ended_at,omitempty"`
}

func (s *Store) StartSession(ctx context.Context, campaignID, title string) (Session, error) {
	if title == "" {
		title = "Session"
	}
	id := "session_" + uuid.NewString()[:8]
	_, err := s.db.ExecContext(ctx, `
INSERT INTO sessions (id, campaign_id, title, status, started_at)
VALUES (?, ?, ?, 'open', datetime('now'))`, id, campaignID, title)
	if err != nil {
		return Session{}, fmt.Errorf("start session: %w", err)
	}
	return s.GetSession(ctx, id)
}

func (s *Store) GetSession(ctx context.Context, id string) (Session, error) {
	var sess Session
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, title, status, started_at, ended_at
FROM sessions WHERE id = ?`, id,
	).Scan(&sess.ID, &sess.CampaignID, &sess.Title, &sess.Status, &sess.StartedAt, &sess.EndedAt)
	if err != nil {
		return Session{}, fmt.Errorf("get session: %w", err)
	}
	return sess, nil
}

func (s *Store) GetOpenSession(ctx context.Context, campaignID string) (Session, error) {
	var sess Session
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, title, status, started_at, ended_at
FROM sessions
WHERE campaign_id = ? AND status = 'open'
ORDER BY started_at DESC
LIMIT 1`, campaignID,
	).Scan(&sess.ID, &sess.CampaignID, &sess.Title, &sess.Status, &sess.StartedAt, &sess.EndedAt)
	if err != nil {
		return Session{}, fmt.Errorf("get open session: %w", err)
	}
	return sess, nil
}

func (s *Store) EndSession(ctx context.Context, id string) (Session, error) {
	res, err := s.db.ExecContext(ctx, `
UPDATE sessions SET status = 'closed', ended_at = datetime('now')
WHERE id = ? AND status = 'open'`, id)
	if err != nil {
		return Session{}, fmt.Errorf("end session: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return Session{}, fmt.Errorf("open session not found: %s", id)
	}
	return s.GetSession(ctx, id)
}
