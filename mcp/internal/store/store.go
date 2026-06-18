package store

import (
	"context"
	"database/sql"
	_ "embed"
	"encoding/json"
	"fmt"
	"strings"

	_ "modernc.org/sqlite"
)

//go:embed schema.sql
var schemaSQL string

const currentSchemaVersion = 1

type Store struct {
	db *sql.DB
}

type Campaign struct {
	ID        string `json:"id"`
	Name      string `json:"name"`
	System    string `json:"system"`
	CreatedAt string `json:"created_at"`
}

type Entity struct {
	ID         string          `json:"id"`
	CampaignID string          `json:"campaign_id"`
	Type       string          `json:"type"`
	Name       string          `json:"name"`
	Summary    string          `json:"summary"`
	Data       json.RawMessage `json:"data"`
	CreatedAt  string          `json:"created_at"`
	UpdatedAt  string          `json:"updated_at"`
}

type Fact struct {
	ID         string  `json:"id"`
	CampaignID string  `json:"campaign_id"`
	EntityID   *string `json:"entity_id,omitempty"`
	Text       string  `json:"text"`
	Visibility string  `json:"visibility"`
	Confidence string  `json:"confidence"`
	SourceType *string `json:"source_type,omitempty"`
	SourceID   *string `json:"source_id,omitempty"`
	CreatedAt  string  `json:"created_at"`
}

type Event struct {
	ID         string          `json:"id"`
	CampaignID string          `json:"campaign_id"`
	SessionID  *string         `json:"session_id,omitempty"`
	Title      string          `json:"title"`
	Summary    string          `json:"summary"`
	EntityIDs  json.RawMessage `json:"entity_ids"`
	CreatedAt  string          `json:"created_at"`
}

type Ruling struct {
	ID         string `json:"id"`
	CampaignID string `json:"campaign_id"`
	Question   string `json:"question"`
	Answer     string `json:"answer"`
	Scope      string `json:"scope"`
	System     string `json:"system"`
	CreatedAt  string `json:"created_at"`
}

func Open(path string) (*Store, error) {
	db, err := sql.Open("sqlite", path)
	if err != nil {
		return nil, fmt.Errorf("open sqlite: %w", err)
	}
	db.SetMaxOpenConns(1)

	s := &Store{db: db}
	if err := s.Migrate(context.Background()); err != nil {
		_ = db.Close()
		return nil, err
	}
	return s, nil
}

func (s *Store) Close() error {
	return s.db.Close()
}

func (s *Store) Migrate(ctx context.Context) error {
	if _, err := s.db.ExecContext(ctx, schemaSQL); err != nil {
		return fmt.Errorf("apply schema: %w", err)
	}

	var version int
	err := s.db.QueryRowContext(ctx, `SELECT COALESCE(MAX(version), 0) FROM schema_migrations`).Scan(&version)
	if err != nil {
		return fmt.Errorf("read schema version: %w", err)
	}
	if version >= currentSchemaVersion {
		return nil
	}

	_, err = s.db.ExecContext(ctx, `INSERT INTO schema_migrations (version) VALUES (?)`, currentSchemaVersion)
	if err != nil {
		return fmt.Errorf("record schema version: %w", err)
	}
	return nil
}

func (s *Store) CreateCampaign(ctx context.Context, c Campaign) error {
	_, err := s.db.ExecContext(ctx,
		`INSERT INTO campaigns (id, name, system, created_at) VALUES (?, ?, ?, COALESCE(NULLIF(?, ''), datetime('now')))`,
		c.ID, c.Name, c.System, c.CreatedAt,
	)
	if err != nil {
		return fmt.Errorf("create campaign: %w", err)
	}
	return nil
}

func (s *Store) GetCampaign(ctx context.Context, id string) (Campaign, error) {
	var c Campaign
	err := s.db.QueryRowContext(ctx,
		`SELECT id, name, system, created_at FROM campaigns WHERE id = ?`, id,
	).Scan(&c.ID, &c.Name, &c.System, &c.CreatedAt)
	if err != nil {
		return Campaign{}, fmt.Errorf("get campaign: %w", err)
	}
	return c, nil
}

func (s *Store) UpsertEntity(ctx context.Context, e Entity) error {
	data := e.Data
	if len(data) == 0 {
		data = json.RawMessage("{}")
	}
	_, err := s.db.ExecContext(ctx, `
INSERT INTO entities (id, campaign_id, type, name, summary, data, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?, COALESCE(NULLIF(?, ''), datetime('now')), datetime('now'))
ON CONFLICT(id) DO UPDATE SET
  type = excluded.type,
  name = excluded.name,
  summary = excluded.summary,
  data = excluded.data,
  updated_at = datetime('now')`,
		e.ID, e.CampaignID, e.Type, e.Name, e.Summary, string(data), e.CreatedAt,
	)
	if err != nil {
		return fmt.Errorf("upsert entity: %w", err)
	}
	return nil
}

func (s *Store) GetEntity(ctx context.Context, id string) (Entity, error) {
	var e Entity
	var data string
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, type, name, summary, data, created_at, updated_at
FROM entities WHERE id = ?`, id,
	).Scan(&e.ID, &e.CampaignID, &e.Type, &e.Name, &e.Summary, &data, &e.CreatedAt, &e.UpdatedAt)
	if err != nil {
		return Entity{}, fmt.Errorf("get entity: %w", err)
	}
	e.Data = json.RawMessage(data)
	return e, nil
}

func (s *Store) AddFact(ctx context.Context, f Fact) error {
	_, err := s.db.ExecContext(ctx, `
INSERT INTO facts (id, campaign_id, entity_id, text, visibility, confidence, source_type, source_id, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, COALESCE(NULLIF(?, ''), datetime('now')))`,
		f.ID, f.CampaignID, f.EntityID, f.Text, f.Visibility, f.Confidence, f.SourceType, f.SourceID, f.CreatedAt,
	)
	if err != nil {
		return fmt.Errorf("add fact: %w", err)
	}
	return nil
}

func (s *Store) AddEvent(ctx context.Context, ev Event) error {
	entityIDs := ev.EntityIDs
	if len(entityIDs) == 0 {
		entityIDs = json.RawMessage("[]")
	}
	_, err := s.db.ExecContext(ctx, `
INSERT INTO events (id, campaign_id, session_id, title, summary, entity_ids, created_at)
VALUES (?, ?, ?, ?, ?, ?, COALESCE(NULLIF(?, ''), datetime('now')))`,
		ev.ID, ev.CampaignID, ev.SessionID, ev.Title, ev.Summary, string(entityIDs), ev.CreatedAt,
	)
	if err != nil {
		return fmt.Errorf("add event: %w", err)
	}
	return nil
}

func (s *Store) AddRuling(ctx context.Context, r Ruling) error {
	_, err := s.db.ExecContext(ctx, `
INSERT INTO rulings (id, campaign_id, question, answer, scope, system, created_at)
VALUES (?, ?, ?, ?, ?, ?, COALESCE(NULLIF(?, ''), datetime('now')))`,
		r.ID, r.CampaignID, r.Question, r.Answer, r.Scope, r.System, r.CreatedAt,
	)
	if err != nil {
		return fmt.Errorf("add ruling: %w", err)
	}
	return nil
}

func (s *Store) SearchWorld(ctx context.Context, campaignID, query string, limit int) ([]Entity, []Fact, error) {
	if limit <= 0 {
		limit = 20
	}
	pattern := "%" + strings.TrimSpace(query) + "%"

	entities, err := s.searchEntities(ctx, campaignID, pattern, limit)
	if err != nil {
		return nil, nil, err
	}
	facts, err := s.searchFacts(ctx, campaignID, pattern, limit)
	if err != nil {
		return nil, nil, err
	}
	return entities, facts, nil
}

func (s *Store) searchEntities(ctx context.Context, campaignID, pattern string, limit int) ([]Entity, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, type, name, summary, data, created_at, updated_at
FROM entities
WHERE campaign_id = ?
  AND (name LIKE ? OR summary LIKE ? OR data LIKE ?)
ORDER BY name
LIMIT ?`, campaignID, pattern, pattern, pattern, limit)
	if err != nil {
		return nil, fmt.Errorf("search entities: %w", err)
	}
	defer rows.Close()

	var out []Entity
	for rows.Next() {
		var e Entity
		var data string
		if err := rows.Scan(&e.ID, &e.CampaignID, &e.Type, &e.Name, &e.Summary, &data, &e.CreatedAt, &e.UpdatedAt); err != nil {
			return nil, fmt.Errorf("scan entity: %w", err)
		}
		e.Data = json.RawMessage(data)
		out = append(out, e)
	}
	return out, rows.Err()
}

func (s *Store) searchFacts(ctx context.Context, campaignID, pattern string, limit int) ([]Fact, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, entity_id, text, visibility, confidence, source_type, source_id, created_at
FROM facts
WHERE campaign_id = ? AND text LIKE ?
ORDER BY created_at DESC
LIMIT ?`, campaignID, pattern, limit)
	if err != nil {
		return nil, fmt.Errorf("search facts: %w", err)
	}
	defer rows.Close()

	var out []Fact
	for rows.Next() {
		var f Fact
		if err := rows.Scan(&f.ID, &f.CampaignID, &f.EntityID, &f.Text, &f.Visibility, &f.Confidence, &f.SourceType, &f.SourceID, &f.CreatedAt); err != nil {
			return nil, fmt.Errorf("scan fact: %w", err)
		}
		out = append(out, f)
	}
	return out, rows.Err()
}

func (s *Store) ListEntitiesByType(ctx context.Context, campaignID, entityType string) ([]Entity, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, type, name, summary, data, created_at, updated_at
FROM entities
WHERE campaign_id = ? AND type = ?
ORDER BY name`, campaignID, entityType)
	if err != nil {
		return nil, fmt.Errorf("list entities: %w", err)
	}
	defer rows.Close()

	var out []Entity
	for rows.Next() {
		var e Entity
		var data string
		if err := rows.Scan(&e.ID, &e.CampaignID, &e.Type, &e.Name, &e.Summary, &data, &e.CreatedAt, &e.UpdatedAt); err != nil {
			return nil, fmt.Errorf("scan entity: %w", err)
		}
		e.Data = json.RawMessage(data)
		out = append(out, e)
	}
	return out, rows.Err()
}

func (s *Store) ListRulings(ctx context.Context, campaignID string) ([]Ruling, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, question, answer, scope, system, created_at
FROM rulings WHERE campaign_id = ? ORDER BY created_at`, campaignID)
	if err != nil {
		return nil, fmt.Errorf("list rulings: %w", err)
	}
	defer rows.Close()

	var out []Ruling
	for rows.Next() {
		var r Ruling
		if err := rows.Scan(&r.ID, &r.CampaignID, &r.Question, &r.Answer, &r.Scope, &r.System, &r.CreatedAt); err != nil {
			return nil, fmt.Errorf("scan ruling: %w", err)
		}
		out = append(out, r)
	}
	return out, rows.Err()
}

func (s *Store) GetRecentEvents(ctx context.Context, campaignID string, limit int) ([]Event, error) {
	if limit <= 0 {
		limit = 10
	}
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, session_id, title, summary, entity_ids, created_at
FROM events
WHERE campaign_id = ?
ORDER BY created_at DESC
LIMIT ?`, campaignID, limit)
	if err != nil {
		return nil, fmt.Errorf("get recent events: %w", err)
	}
	defer rows.Close()

	var out []Event
	for rows.Next() {
		var ev Event
		var entityIDs string
		if err := rows.Scan(&ev.ID, &ev.CampaignID, &ev.SessionID, &ev.Title, &ev.Summary, &entityIDs, &ev.CreatedAt); err != nil {
			return nil, fmt.Errorf("scan event: %w", err)
		}
		ev.EntityIDs = json.RawMessage(entityIDs)
		out = append(out, ev)
	}
	return out, rows.Err()
}

func (s *Store) ListActivePlots(ctx context.Context, campaignID string) ([]Entity, error) {
	plots, err := s.ListEntitiesByType(ctx, campaignID, "plot")
	if err != nil {
		return nil, err
	}
	var active []Entity
	for _, p := range plots {
		var data map[string]any
		if err := json.Unmarshal(p.Data, &data); err != nil {
			continue
		}
		status, _ := data["status"].(string)
		if status == "" || status == "active" {
			active = append(active, p)
		}
	}
	return active, nil
}

func (s *Store) SearchRulings(ctx context.Context, campaignID, query string, limit int) ([]Ruling, error) {
	if limit <= 0 {
		limit = 20
	}
	pattern := "%" + strings.TrimSpace(query) + "%"
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, question, answer, scope, system, created_at
FROM rulings
WHERE campaign_id = ? AND (question LIKE ? OR answer LIKE ?)
ORDER BY created_at DESC
LIMIT ?`, campaignID, pattern, pattern, limit)
	if err != nil {
		return nil, fmt.Errorf("search rulings: %w", err)
	}
	defer rows.Close()

	var out []Ruling
	for rows.Next() {
		var r Ruling
		if err := rows.Scan(&r.ID, &r.CampaignID, &r.Question, &r.Answer, &r.Scope, &r.System, &r.CreatedAt); err != nil {
			return nil, fmt.Errorf("scan ruling: %w", err)
		}
		out = append(out, r)
	}
	return out, rows.Err()
}
