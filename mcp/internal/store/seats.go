package store

import (
	"context"
	"fmt"
	"strings"

	"github.com/google/uuid"
)

type CampaignSeat struct {
	ID               string  `json:"id"`
	CampaignID       string  `json:"campaign_id"`
	SeatType         string  `json:"seat_type"`
	Controller       string  `json:"controller"`
	ControllerUserID *string `json:"controller_user_id,omitempty"`
	ActorID          *string `json:"actor_id,omitempty"`
	DisplayName      string  `json:"display_name"`
	Status           string  `json:"status"`
	CreatedAt        string  `json:"created_at"`
	UpdatedAt        string  `json:"updated_at"`
}

func normalizeSeatType(raw string) (string, error) {
	t := strings.ToLower(strings.TrimSpace(raw))
	switch t {
	case "dm", "player":
		return t, nil
	default:
		return "", fmt.Errorf("invalid seat_type: %s", raw)
	}
}

func normalizeController(raw string) (string, error) {
	c := strings.ToLower(strings.TrimSpace(raw))
	switch c {
	case "human", "ai":
		return c, nil
	default:
		return "", fmt.Errorf("invalid controller: %s", raw)
	}
}

func normalizeSeatStatus(raw string) (string, error) {
	if raw == "" {
		return "active", nil
	}
	s := strings.ToLower(strings.TrimSpace(raw))
	switch s {
	case "active", "vacant", "paused":
		return s, nil
	default:
		return "", fmt.Errorf("invalid status: %s", raw)
	}
}

func (s *Store) CreateSeat(ctx context.Context, seat CampaignSeat) (CampaignSeat, error) {
	seatType, err := normalizeSeatType(seat.SeatType)
	if err != nil {
		return CampaignSeat{}, err
	}
	controller, err := normalizeController(seat.Controller)
	if err != nil {
		return CampaignSeat{}, err
	}
	status, err := normalizeSeatStatus(seat.Status)
	if err != nil {
		return CampaignSeat{}, err
	}
	if seat.ID == "" {
		seat.ID = "seat_" + uuid.NewString()[:8]
	}
	if seat.DisplayName == "" {
		seat.DisplayName = seatType
	}

	var userID any
	if seat.ControllerUserID != nil && strings.TrimSpace(*seat.ControllerUserID) != "" {
		userID = strings.TrimSpace(*seat.ControllerUserID)
	}
	var actorID any
	if seat.ActorID != nil && strings.TrimSpace(*seat.ActorID) != "" {
		actorID = strings.TrimSpace(*seat.ActorID)
	}

	_, err = s.db.ExecContext(ctx, `
INSERT INTO campaign_seats (
    id, campaign_id, seat_type, controller, controller_user_id,
    actor_id, display_name, status, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))`,
		seat.ID, seat.CampaignID, seatType, controller, userID,
		actorID, seat.DisplayName, status,
	)
	if err != nil {
		return CampaignSeat{}, fmt.Errorf("create seat: %w", err)
	}
	return s.GetSeat(ctx, seat.ID)
}

func (s *Store) GetSeat(ctx context.Context, id string) (CampaignSeat, error) {
	var seat CampaignSeat
	var userID, actorID *string
	err := s.db.QueryRowContext(ctx, `
SELECT id, campaign_id, seat_type, controller, controller_user_id,
       actor_id, display_name, status, created_at, updated_at
FROM campaign_seats WHERE id = ?`, id,
	).Scan(
		&seat.ID, &seat.CampaignID, &seat.SeatType, &seat.Controller, &userID,
		&actorID, &seat.DisplayName, &seat.Status, &seat.CreatedAt, &seat.UpdatedAt,
	)
	if err != nil {
		return CampaignSeat{}, fmt.Errorf("get seat: %w", err)
	}
	seat.ControllerUserID = userID
	seat.ActorID = actorID
	return seat, nil
}

func (s *Store) ListCampaignSeats(ctx context.Context, campaignID string) ([]CampaignSeat, error) {
	rows, err := s.db.QueryContext(ctx, `
SELECT id, campaign_id, seat_type, controller, controller_user_id,
       actor_id, display_name, status, created_at, updated_at
FROM campaign_seats
WHERE campaign_id = ?
ORDER BY CASE seat_type WHEN 'dm' THEN 0 ELSE 1 END, display_name`, campaignID)
	if err != nil {
		return nil, fmt.Errorf("list campaign seats: %w", err)
	}
	defer rows.Close()

	var out []CampaignSeat
	for rows.Next() {
		var seat CampaignSeat
		var userID, actorID *string
		if err := rows.Scan(
			&seat.ID, &seat.CampaignID, &seat.SeatType, &seat.Controller, &userID,
			&actorID, &seat.DisplayName, &seat.Status, &seat.CreatedAt, &seat.UpdatedAt,
		); err != nil {
			return nil, err
		}
		seat.ControllerUserID = userID
		seat.ActorID = actorID
		out = append(out, seat)
	}
	return out, rows.Err()
}

func (s *Store) CreatePlayerSeat(ctx context.Context, campaignID, actorID, displayName string) (CampaignSeat, error) {
	actorID = strings.TrimSpace(actorID)
	if actorID == "" {
		return CampaignSeat{}, fmt.Errorf("actor_id required")
	}
	entity, err := s.GetEntity(ctx, actorID)
	if err != nil {
		return CampaignSeat{}, fmt.Errorf("actor not found: %s", actorID)
	}
	if entity.CampaignID != campaignID {
		return CampaignSeat{}, fmt.Errorf("actor not in campaign")
	}
	if displayName == "" {
		displayName = entity.Name
	}
	actor := actorID
	return s.CreateSeat(ctx, CampaignSeat{
		CampaignID:  campaignID,
		SeatType:    "player",
		Controller:  "ai",
		ActorID:     &actor,
		DisplayName: displayName,
		Status:      "active",
	})
}

func (s *Store) AssignSeatController(ctx context.Context, seatID, controller string, userID *string) (CampaignSeat, error) {
	controller, err := normalizeController(controller)
	if err != nil {
		return CampaignSeat{}, err
	}
	if _, err := s.GetSeat(ctx, seatID); err != nil {
		return CampaignSeat{}, err
	}

	var userVal any
	if controller == "human" {
		if userID == nil || strings.TrimSpace(*userID) == "" {
			return CampaignSeat{}, fmt.Errorf("controller_user_id required for human controller")
		}
		userVal = strings.TrimSpace(*userID)
	}

	res, err := s.db.ExecContext(ctx, `
UPDATE campaign_seats
SET controller = ?, controller_user_id = ?, updated_at = datetime('now')
WHERE id = ?`, controller, userVal, seatID)
	if err != nil {
		return CampaignSeat{}, fmt.Errorf("assign seat controller: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return CampaignSeat{}, fmt.Errorf("seat not found: %s", seatID)
	}
	return s.GetSeat(ctx, seatID)
}

func (s *Store) CountCampaignSeats(ctx context.Context, campaignID string) (int, error) {
	var n int
	err := s.db.QueryRowContext(ctx, `SELECT COUNT(*) FROM campaign_seats WHERE campaign_id = ?`, campaignID).Scan(&n)
	return n, err
}
