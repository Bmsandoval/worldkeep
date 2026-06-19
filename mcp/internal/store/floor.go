package store

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"
)

type SessionFloor struct {
	SessionID                string   `json:"session_id"`
	FloorSeatID              *string  `json:"floor_seat_id,omitempty"`
	PartyBeatQueue           []string `json:"party_beat_queue"`
	AwaitingPlayerCheckpoint bool     `json:"awaiting_player_checkpoint"`
}

func (s *Store) InitSessionFloor(ctx context.Context, sessionID, campaignID string) (SessionFloor, error) {
	var floorSeatID *string
	seats, err := s.ListCampaignSeats(ctx, campaignID)
	if err != nil {
		return SessionFloor{}, err
	}
	for _, seat := range seats {
		if seat.SeatType == "player" && seat.Status == "active" {
			id := seat.ID
			floorSeatID = &id
			break
		}
	}
	queue, _ := json.Marshal([]string{})
	_, err = s.db.ExecContext(ctx, `
INSERT INTO session_floor (session_id, floor_seat_id, party_beat_queue, awaiting_player_checkpoint)
VALUES (?, ?, ?, 0)`, sessionID, floorSeatID, string(queue))
	if err != nil {
		return SessionFloor{}, fmt.Errorf("init session floor: %w", err)
	}
	return s.GetSessionFloor(ctx, sessionID)
}

func (s *Store) GetSessionFloor(ctx context.Context, sessionID string) (SessionFloor, error) {
	var floor SessionFloor
	var floorSeatID *string
	var queueRaw string
	var awaiting int
	err := s.db.QueryRowContext(ctx, `
SELECT session_id, floor_seat_id, party_beat_queue, awaiting_player_checkpoint
FROM session_floor WHERE session_id = ?`, sessionID,
	).Scan(&floor.SessionID, &floorSeatID, &queueRaw, &awaiting)
	if err != nil {
		return SessionFloor{}, fmt.Errorf("get session floor: %w", err)
	}
	floor.FloorSeatID = floorSeatID
	floor.AwaitingPlayerCheckpoint = awaiting != 0
	_ = json.Unmarshal([]byte(queueRaw), &floor.PartyBeatQueue)
	if floor.PartyBeatQueue == nil {
		floor.PartyBeatQueue = []string{}
	}
	return floor, nil
}

func (s *Store) SetSessionFloor(ctx context.Context, sessionID string, floorSeatID *string, queue []string, awaiting bool) (SessionFloor, error) {
	if queue == nil {
		queue = []string{}
	}
	queueRaw, err := json.Marshal(queue)
	if err != nil {
		return SessionFloor{}, err
	}
	awaitingInt := 0
	if awaiting {
		awaitingInt = 1
	}
	res, err := s.db.ExecContext(ctx, `
UPDATE session_floor
SET floor_seat_id = ?, party_beat_queue = ?, awaiting_player_checkpoint = ?
WHERE session_id = ?`, floorSeatID, string(queueRaw), awaitingInt, sessionID)
	if err != nil {
		return SessionFloor{}, fmt.Errorf("set session floor: %w", err)
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		return SessionFloor{}, fmt.Errorf("session floor not found: %s", sessionID)
	}
	return s.GetSessionFloor(ctx, sessionID)
}

func (s *Store) SetFloorSeat(ctx context.Context, sessionID, seatID string) (SessionFloor, error) {
	seatID = strings.TrimSpace(seatID)
	if seatID == "" {
		return SessionFloor{}, fmt.Errorf("floor_seat_id required")
	}
	current, err := s.GetSessionFloor(ctx, sessionID)
	if err != nil {
		return SessionFloor{}, err
	}
	sid := seatID
	return s.SetSessionFloor(ctx, sessionID, &sid, current.PartyBeatQueue, current.AwaitingPlayerCheckpoint)
}
