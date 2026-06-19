# Handoff playtest — v1.5.0 (experimental / icebox)

> **Icebox:** Participant handoff is not active product direction. This script exercises **prototype** MCP on `develop` only — see [icebox.md](./icebox.md).

Scenario from [participant-handoff.md](./participant-handoff.md) §8: solo AI table → friend joins companion → leaves back to AI.

## Automated (CI)

```bash
make test
# covers store/handoff_test.go and internal/mcp/handlers_handoff_test.go
```

Or:

```bash
./scripts/playtest-handoff.sh
```

## Manual MCP flow

Prerequisites: `make seed` or `make backfill-seats`, `make serve` or `make mcp`.

1. **`list_campaign_seats`** — expect `seat_dm`, `seat_player_1` (Kael), `seat_player_2` (Lia), all `controller: ai`.
2. **`start_session`** — title e.g. "Dockside night".
3. **`get_session_floor`** — default `floor_seat_id: seat_player_1` (player-led).
4. **`handoff_seat`** — `seat_player_2`, `controller: human`, `controller_user_id: user_alex`, `reason: Friend joins as Lia`.
5. **`get_entity`** `companion_lia` — unchanged personality/data (continuity).
6. **`handoff_seat`** — `seat_dm` to human DM (optional acceptance step).
7. **`release_seat_to_ai`** — `seat_player_2`, `reason: Player left`.
8. **`get_recent_events`** — includes `seat_handoff` audit entries.

## Floor modes (reference)

| Mode | Behavior |
| ---- | -------- |
| **Player-led** (default) | `floor_seat_id` points at active player seat; DM responds when invoked |
| **Party beat** | `party_beat_queue` lists seat ids in order; `awaiting_player_checkpoint` pauses AI between beats |
| **Open table** | Clients allow all human seats to interject; set floor manually as needed |

Floor state is stored per open session in `session_floor`. v1.5 exposes read via `get_session_floor`; beat queue editing was planned for seat UI (v1.6, now **icebox**).
