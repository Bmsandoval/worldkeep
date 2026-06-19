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
	Notes      string  `json:"notes,omitempty"`
	Summary    string  `json:"summary,omitempty"`
	StartedAt  string  `json:"started_at"`
	EndedAt    *string `json:"ended_at,omitempty"`
}

type SessionChange struct {
	ID        int64  `json:"id"`
	SessionID string `json:"session_id"`
	EntityID  string `json:"entity_id"`
	ChangeOp  string `json:"change_op"`
	CreatedAt string `json:"created_at"`
}

type SessionWorkspace struct {
	Session          Session         `json:"session"`
	Events           []Event         `json:"events"`
	ModifiedEntities []SessionChange `json:"modified_entities"`
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
	if _, err := s.InitSessionFloor(ctx, id, campaignID); err != nil {
		return Session{}, err
	}
	return s.GetSession(ctx, id)
}

func (s *Store) GetSession(ctx context.Context, id string) (Session, error) {
	var sess Session
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, title, status,
       COALESCE(notes, ''), COALESCE(summary, ''),
       started_at, ended_at
FROM sessions WHERE id = ?`, id,
	).Scan(&sess.ID, &sess.CampaignID, &sess.Title, &sess.Status, &sess.Notes, &sess.Summary, &sess.StartedAt, &sess.EndedAt)
	if err != nil {
		return Session{}, fmt.Errorf("get session: %w", err)
	}
	return sess, nil
}

func (s *Store) GetOpenSession(ctx context.Context, campaignID string) (Session, error) {
	var sess Session
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, title, status,
       COALESCE(notes, ''), COALESCE(summary, ''),
       started_at, ended_at
FROM sessions
WHERE campaign_id = ? AND status = 'open'
ORDER BY started_at DESC
LIMIT 1`, campaignID,
	).Scan(&sess.ID, &sess.CampaignID, &sess.Title, &sess.Status, &sess.Notes, &sess.Summary, &sess.StartedAt, &sess.EndedAt)
	if err != nil {
		return Session{}, fmt.Errorf("get open session: %w", err)
	}
	return sess, nil
}

func (s *Store) EndSession(ctx context.Context, id, summary string) (Session, error) {
	res, err := s.db.ExecContext(ctx, `
UPDATE sessions SET status = 'closed', ended_at = datetime('now'), summary = COALESCE(NULLIF(?, ''), summary)
WHERE id = ? AND status = 'open'`, summary, id)
	if err != nil {
		return Session{}, fmt.Errorf("end session: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return Session{}, fmt.Errorf("open session not found: %s", id)
	}
	return s.GetSession(ctx, id)
}

func (s *Store) UpdateSessionNotes(ctx context.Context, id, notes string) (Session, error) {
	res, err := s.db.ExecContext(ctx, `UPDATE sessions SET notes = ? WHERE id = ?`, notes, id)
	if err != nil {
		return Session{}, fmt.Errorf("update session notes: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return Session{}, fmt.Errorf("session not found: %s", id)
	}
	return s.GetSession(ctx, id)
}

func (s *Store) RecordSessionChange(ctx context.Context, sessionID, entityID, changeOp string) error {
	if sessionID == "" || entityID == "" {
		return nil
	}
	_, err := s.db.ExecContext(ctx, `
INSERT INTO session_changes (session_id, entity_id, change_op)
VALUES (?, ?, ?)`, sessionID, entityID, changeOp)
	if err != nil {
		return fmt.Errorf("record session change: %w", err)
	}
	return nil
}

func (s *Store) ListSessionChanges(ctx context.Context, sessionID string) ([]SessionChange, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, session_id, entity_id, change_op, created_at
FROM session_changes WHERE session_id = ? ORDER BY created_at`, sessionID)
	if err != nil {
		return nil, fmt.Errorf("list session changes: %w", err)
	}
	defer rows.Close()

	var out []SessionChange
	for rows.Next() {
		var ch SessionChange
		if err := rows.Scan(&ch.ID, &ch.SessionID, &ch.EntityID, &ch.ChangeOp, &ch.CreatedAt); err != nil {
			return nil, err
		}
		out = append(out, ch)
	}
	return out, rows.Err()
}

func (s *Store) ListEventsBySession(ctx context.Context, sessionID string) ([]Event, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, session_id, title, summary, entity_ids, created_at
FROM events WHERE session_id = ? ORDER BY created_at`, sessionID)
	if err != nil {
		return nil, fmt.Errorf("list session events: %w", err)
	}
	defer rows.Close()

	var out []Event
	for rows.Next() {
		var ev Event
		var entityIDs string
		if err := rows.Scan(&ev.ID, &ev.CampaignID, &ev.SessionID, &ev.Title, &ev.Summary, &entityIDs, &ev.CreatedAt); err != nil {
			return nil, err
		}
		ev.EntityIDs = []byte(entityIDs)
		out = append(out, ev)
	}
	return out, rows.Err()
}

func (s *Store) ListSessions(ctx context.Context, campaignID string, limit int) ([]Session, error) {
	if limit <= 0 {
		limit = 20
	}
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, title, status,
       COALESCE(notes, ''), COALESCE(summary, ''),
       started_at, ended_at
FROM sessions
WHERE campaign_id = ?
ORDER BY started_at DESC
LIMIT ?`, campaignID, limit)
	if err != nil {
		return nil, fmt.Errorf("list sessions: %w", err)
	}
	defer rows.Close()

	var out []Session
	for rows.Next() {
		var sess Session
		if err := rows.Scan(&sess.ID, &sess.CampaignID, &sess.Title, &sess.Status, &sess.Notes, &sess.Summary, &sess.StartedAt, &sess.EndedAt); err != nil {
			return nil, err
		}
		out = append(out, sess)
	}
	return out, rows.Err()
}

func (s *Store) GetSessionWorkspace(ctx context.Context, sessionID string) (SessionWorkspace, error) {
	sess, err := s.GetSession(ctx, sessionID)
	if err != nil {
		return SessionWorkspace{}, err
	}
	events, err := s.ListEventsBySession(ctx, sessionID)
	if err != nil {
		return SessionWorkspace{}, err
	}
	changes, err := s.ListSessionChanges(ctx, sessionID)
	if err != nil {
		return SessionWorkspace{}, err
	}
	return SessionWorkspace{
		Session:          sess,
		Events:           events,
		ModifiedEntities: changes,
	}, nil
}

func (s *Store) GetCampaignRole(ctx context.Context, campaignID string) (string, error) {
	var role string
	err := s.db.QueryRowContext(ctx, `SELECT role FROM campaign_roles WHERE campaign_id = ?`, campaignID).Scan(&role)
	if err != nil {
		return "owner", nil
	}
	return role, nil
}

func (s *Store) SetCampaignRole(ctx context.Context, campaignID, role string) error {
	if role == "" {
		role = "owner"
	}
	_, err := s.db.ExecContext(ctx, `
INSERT INTO campaign_roles (campaign_id, role) VALUES (?, ?)
ON CONFLICT(campaign_id) DO UPDATE SET role = excluded.role`, campaignID, role)
	if err != nil {
		return fmt.Errorf("set campaign role: %w", err)
	}
	return nil
}
