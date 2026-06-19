<?php

namespace App\Services\WorldKeep;

use App\Services\WorldKeep\Concerns\HasSqlHelpers;
use App\Services\WorldKeep\Concerns\MigratesSchema;
use App\Services\WorldKeep\Data\Campaign;
use App\Services\WorldKeep\Data\CampaignSeat;
use App\Services\WorldKeep\Data\ConflictWarning;
use App\Services\WorldKeep\Data\Entity;
use App\Services\WorldKeep\Data\Event;
use App\Services\WorldKeep\Data\Fact;
use App\Services\WorldKeep\Data\PendingUpdate;
use App\Services\WorldKeep\Data\Ruling;
use App\Services\WorldKeep\Data\SeatHandoffResult;
use App\Services\WorldKeep\Data\Session;
use App\Services\WorldKeep\Data\SessionChange;
use App\Services\WorldKeep\Data\SessionFloor;
use App\Services\WorldKeep\Data\SessionWorkspace;
use App\Services\WorldKeep\Data\WorldChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class Store
{
    use HasSqlHelpers;
    use MigratesSchema;

    public function createCampaign(Campaign $campaign): void
    {
        DB::insert(
            'INSERT INTO campaigns (id, name, system, created_at) VALUES (?, ?, ?, ?)',
            [
                $campaign->id,
                $campaign->name,
                $campaign->system,
                $this->coalesceTimestamp($campaign->createdAt),
            ]
        );
    }

    public function getCampaign(string $id): Campaign
    {
        $row = DB::selectOne(
            'SELECT id, name, system, created_at FROM campaigns WHERE id = ?',
            [$id]
        );

        if ($row === null) {
            throw new RuntimeException("get campaign: not found: {$id}");
        }

        return Campaign::fromRow($row);
    }

    public function upsertEntity(Entity $entity): void
    {
        $data = $entity->data !== '' ? $entity->data : '{}';
        $now = $this->nowTimestamp();

        $this->upsertRow(
            'entities',
            [
                'id' => $entity->id,
                'campaign_id' => $entity->campaignId,
                'type' => $entity->type,
                'name' => $entity->name,
                'summary' => $entity->summary,
                'data' => $data,
                'created_at' => $this->coalesceTimestamp($entity->createdAt),
                'updated_at' => $now,
            ],
            ['id'],
            ['type', 'name', 'summary', 'data', 'updated_at']
        );
    }

    public function getEntity(string $id): Entity
    {
        $row = DB::selectOne(
            'SELECT id, campaign_id, type, name, summary, data, created_at, updated_at FROM entities WHERE id = ?',
            [$id]
        );

        if ($row === null) {
            throw new RuntimeException("get entity: not found: {$id}");
        }

        return Entity::fromRow($row);
    }

    public function addFact(Fact $fact): void
    {
        DB::insert(
            'INSERT INTO facts (id, campaign_id, entity_id, text, visibility, confidence, source_type, source_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $fact->id,
                $fact->campaignId,
                $fact->entityId,
                $fact->text,
                $fact->visibility,
                $fact->confidence,
                $fact->sourceType,
                $fact->sourceId,
                $this->coalesceTimestamp($fact->createdAt),
            ]
        );
    }

    public function addEvent(Event $event): void
    {
        $entityIds = $event->entityIds !== '' ? $event->entityIds : '[]';

        DB::insert(
            'INSERT INTO events (id, campaign_id, session_id, title, summary, entity_ids, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $event->id,
                $event->campaignId,
                $event->sessionId,
                $event->title,
                $event->summary,
                $entityIds,
                $this->coalesceTimestamp($event->createdAt),
            ]
        );
    }

    public function addRuling(Ruling $ruling): void
    {
        DB::insert(
            'INSERT INTO rulings (id, campaign_id, question, answer, scope, system, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $ruling->id,
                $ruling->campaignId,
                $ruling->question,
                $ruling->answer,
                $ruling->scope,
                $ruling->system,
                $this->coalesceTimestamp($ruling->createdAt),
            ]
        );
    }

    /**
     * @return array{0: list<Entity>, 1: list<Fact>}
     */
    public function searchWorld(string $campaignId, string $query, int $limit, ReadScope $scope): array
    {
        if ($limit <= 0) {
            $limit = 20;
        }

        $pattern = '%'.trim($query).'%';

        return [
            $this->searchEntities($campaignId, $pattern, $limit, $scope),
            $this->searchFacts($campaignId, $pattern, $limit, $scope),
        ];
    }

    /**
     * @return list<Entity>
     */
    private function searchEntities(string $campaignId, string $pattern, int $limit, ReadScope $scope): array
    {
        $sql = '
            SELECT id, campaign_id, type, name, summary, data, created_at, updated_at
            FROM entities
            WHERE campaign_id = ?
              AND (name LIKE ? OR summary LIKE ? OR data LIKE ?)';
        $bindings = [$campaignId, $pattern, $pattern, $pattern];

        if (! $scope->includesDmOnly()) {
            $sql .= " AND type != 'secret'";
        }

        $sql .= ' ORDER BY name LIMIT ?';
        $bindings[] = $limit;

        return array_map(
            static fn (object $row) => Entity::fromRow($row),
            DB::select($sql, $bindings)
        );
    }

    /**
     * @return list<Fact>
     */
    private function searchFacts(string $campaignId, string $pattern, int $limit, ReadScope $scope): array
    {
        $sql = '
            SELECT id, campaign_id, entity_id, text, visibility, confidence, source_type, source_id, created_at
            FROM facts
            WHERE campaign_id = ? AND text LIKE ?';
        $bindings = [$campaignId, $pattern];

        if (! $scope->includesDmOnly()) {
            $sql .= " AND visibility != 'dm_only'";
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ?';
        $bindings[] = $limit;

        return array_map(
            static fn (object $row) => Fact::fromRow($row),
            DB::select($sql, $bindings)
        );
    }

    /**
     * @return list<Entity>
     */
    public function listEntitiesByType(string $campaignId, string $entityType, ReadScope $scope): array
    {
        if (! $scope->includesDmOnly() && $entityType === 'secret') {
            return [];
        }

        $sql = '
            SELECT id, campaign_id, type, name, summary, data, created_at, updated_at
            FROM entities
            WHERE campaign_id = ?';
        $bindings = [$campaignId];

        // An empty type means "all types" (e.g. the World browser's "All" tab).
        if ($entityType !== '') {
            $sql .= ' AND type = ?';
            $bindings[] = $entityType;
        }

        if (! $scope->includesDmOnly()) {
            $sql .= " AND type != 'secret'";
        }

        $sql .= ' ORDER BY name';

        return array_map(
            static fn (object $row) => Entity::fromRow($row),
            DB::select($sql, $bindings)
        );
    }

    /**
     * @return list<Event>
     */
    public function getRecentEvents(string $campaignId, int $limit): array
    {
        if ($limit <= 0) {
            $limit = 10;
        }

        return array_map(
            static fn (object $row) => Event::fromRow($row),
            DB::select(
                'SELECT id, campaign_id, session_id, title, summary, entity_ids, created_at
                 FROM events
                 WHERE campaign_id = ?
                 ORDER BY created_at DESC
                 LIMIT ?',
                [$campaignId, $limit]
            )
        );
    }

    /**
     * @return list<Entity>
     */
    public function listActivePlots(string $campaignId, ReadScope $scope): array
    {
        $plots = $this->listEntitiesByType($campaignId, 'plot', $scope);
        $active = [];

        $resolvedStatuses = ['resolved', 'closed', 'complete', 'completed', 'done', 'failed', 'abandoned', 'cancelled', 'canceled'];

        foreach ($plots as $plot) {
            $data = json_decode($plot->data, true);
            $status = is_array($data) ? strtolower((string) ($data['status'] ?? '')) : '';
            if (! in_array($status, $resolvedStatuses, true)) {
                $active[] = $plot;
            }
        }

        return $active;
    }

    /**
     * @return list<Ruling>
     */
    public function searchRulings(string $campaignId, string $query, int $limit): array
    {
        if ($limit <= 0) {
            $limit = 20;
        }

        $pattern = '%'.trim($query).'%';

        return array_map(
            static fn (object $row) => Ruling::fromRow($row),
            DB::select(
                'SELECT id, campaign_id, question, answer, scope, system, created_at
                 FROM rulings
                 WHERE campaign_id = ? AND (question LIKE ? OR answer LIKE ?)
                 ORDER BY created_at DESC
                 LIMIT ?',
                [$campaignId, $pattern, $pattern, $limit]
            )
        );
    }

    /**
     * @return array{0: list<Entity>, 1: list<Fact>}
     */
    public function searchWorldHybrid(string $campaignId, string $query, int $limit, ReadScope $scope): array
    {
        if ($limit <= 0) {
            $limit = 20;
        }

        $tokens = $this->tokenizeSearch($query);
        if ($tokens === []) {
            return $this->searchWorld($campaignId, $query, $limit, $scope);
        }

        /** @var array<string, Entity> $seenEntities */
        $seenEntities = [];
        /** @var array<string, Fact> $seenFacts */
        $seenFacts = [];

        foreach ($tokens as $token) {
            [$entities, $facts] = $this->searchWorld($campaignId, $token, $limit, $scope);
            foreach ($entities as $entity) {
                $seenEntities[$entity->id] = $entity;
            }
            foreach ($facts as $fact) {
                $seenFacts[$fact->id] = $fact;
            }
        }

        $entities = [];
        foreach ($seenEntities as $entity) {
            $entities[] = $entity;
            if (count($entities) >= $limit) {
                break;
            }
        }

        $facts = [];
        foreach ($seenFacts as $fact) {
            $facts[] = $fact;
            if (count($facts) >= $limit) {
                break;
            }
        }

        return [$entities, $facts];
    }

    /** @return list<string> */
    private function tokenizeSearch(string $query): array
    {
        $words = preg_split('/\s+/', strtolower(trim($query))) ?: [];
        $tokens = [];
        $seen = [];

        foreach ($words as $word) {
            $word = trim($word, ".,!?\"'");
            if (strlen($word) < 3 || isset($seen[$word])) {
                continue;
            }
            $seen[$word] = true;
            $tokens[] = $word;
        }

        return $tokens;
    }

    public function startSession(string $campaignId, string $title): Session
    {
        if ($title === '') {
            $title = 'Session';
        }

        $id = 'session_'.substr((string) Str::uuid(), 0, 8);
        $sessions = $this->sessionsTable();

        DB::insert(
            "INSERT INTO {$sessions} (id, campaign_id, title, status, started_at) VALUES (?, ?, ?, 'open', ?)",
            [$id, $campaignId, $title, $this->nowTimestamp()]
        );

        $this->initSessionFloor($id, $campaignId);

        return $this->getSession($id);
    }

    public function getSession(string $id): Session
    {
        $sessions = $this->sessionsTable();
        $row = DB::selectOne(
            "SELECT id, campaign_id, title, status,
                    COALESCE(notes, '') AS notes, COALESCE(summary, '') AS summary,
                    started_at, ended_at
             FROM {$sessions} WHERE id = ?",
            [$id]
        );

        if ($row === null) {
            throw new RuntimeException("get session: not found: {$id}");
        }

        return Session::fromRow($row);
    }

    public function getOpenSession(string $campaignId): Session
    {
        $sessions = $this->sessionsTable();
        $row = DB::selectOne(
            "SELECT id, campaign_id, title, status,
                    COALESCE(notes, '') AS notes, COALESCE(summary, '') AS summary,
                    started_at, ended_at
             FROM {$sessions}
             WHERE campaign_id = ? AND status = 'open'
             ORDER BY started_at DESC
             LIMIT 1",
            [$campaignId]
        );

        if ($row === null) {
            throw new RuntimeException("get open session: not found for campaign: {$campaignId}");
        }

        return Session::fromRow($row);
    }

    public function endSession(string $id, string $summary): Session
    {
        $sessions = $this->sessionsTable();
        $updated = DB::update(
            "UPDATE {$sessions}
             SET status = 'closed', ended_at = ?, summary = COALESCE(NULLIF(?, ''), summary)
             WHERE id = ? AND status = 'open'",
            [$this->nowTimestamp(), $summary, $id]
        );

        if ($updated === 0) {
            throw new RuntimeException("open session not found: {$id}");
        }

        return $this->getSession($id);
    }

    public function updateSessionNotes(string $id, string $notes): Session
    {
        $sessions = $this->sessionsTable();
        $updated = DB::update(
            "UPDATE {$sessions} SET notes = ? WHERE id = ?",
            [$notes, $id]
        );

        if ($updated === 0) {
            throw new RuntimeException("session not found: {$id}");
        }

        return $this->getSession($id);
    }

    public function recordSessionChange(string $sessionId, string $entityId, string $changeOp): void
    {
        if ($sessionId === '' || $entityId === '') {
            return;
        }

        DB::insert(
            'INSERT INTO session_changes (session_id, entity_id, change_op) VALUES (?, ?, ?)',
            [$sessionId, $entityId, $changeOp]
        );
    }

    /**
     * @return list<SessionChange>
     */
    public function listSessionChanges(string $sessionId): array
    {
        return array_map(
            static fn (object $row) => SessionChange::fromRow($row),
            DB::select(
                'SELECT id, session_id, entity_id, change_op, created_at
                 FROM session_changes WHERE session_id = ? ORDER BY created_at',
                [$sessionId]
            )
        );
    }

    /**
     * @return list<Event>
     */
    public function listEventsBySession(string $sessionId): array
    {
        return array_map(
            static fn (object $row) => Event::fromRow($row),
            DB::select(
                'SELECT id, campaign_id, session_id, title, summary, entity_ids, created_at
                 FROM events WHERE session_id = ? ORDER BY created_at',
                [$sessionId]
            )
        );
    }

    /**
     * @return list<Session>
     */
    public function listSessions(string $campaignId, int $limit): array
    {
        if ($limit <= 0) {
            $limit = 20;
        }

        $sessions = $this->sessionsTable();

        return array_map(
            static fn (object $row) => Session::fromRow($row),
            DB::select(
                "SELECT id, campaign_id, title, status,
                        COALESCE(notes, '') AS notes, COALESCE(summary, '') AS summary,
                        started_at, ended_at
                 FROM {$sessions}
                 WHERE campaign_id = ?
                 ORDER BY started_at DESC
                 LIMIT ?",
                [$campaignId, $limit]
            )
        );
    }

    public function getSessionWorkspace(string $sessionId): SessionWorkspace
    {
        return new SessionWorkspace(
            session: $this->getSession($sessionId),
            events: $this->listEventsBySession($sessionId),
            modifiedEntities: $this->listSessionChanges($sessionId),
        );
    }

    public function setCampaignRole(string $campaignId, string $role): void
    {
        if ($role === '') {
            $role = 'owner';
        }

        $this->upsertRow(
            'campaign_roles',
            [
                'campaign_id' => $campaignId,
                'role' => $role,
            ],
            ['campaign_id'],
            ['role']
        );
    }

    public function createSeat(CampaignSeat $seat): CampaignSeat
    {
        $seatType = $this->normalizeSeatType($seat->seatType);
        $controller = $this->normalizeController($seat->controller);
        $status = $this->normalizeSeatStatus($seat->status);

        $id = $seat->id !== '' ? $seat->id : 'seat_'.substr((string) Str::uuid(), 0, 8);
        $displayName = $seat->displayName !== '' ? $seat->displayName : $seatType;
        $now = $this->nowTimestamp();

        DB::insert(
            'INSERT INTO campaign_seats (
                id, campaign_id, seat_type, controller, controller_user_id,
                actor_id, display_name, status, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id,
                $seat->campaignId,
                $seatType,
                $controller,
                $this->nullableTrim($seat->controllerUserId),
                $this->nullableTrim($seat->actorId),
                $displayName,
                $status,
                $now,
                $now,
            ]
        );

        return $this->getSeat($id);
    }

    public function getSeat(string $id): CampaignSeat
    {
        $row = DB::selectOne(
            'SELECT id, campaign_id, seat_type, controller, controller_user_id,
                    actor_id, display_name, status, created_at, updated_at
             FROM campaign_seats WHERE id = ?',
            [$id]
        );

        if ($row === null) {
            throw new RuntimeException("get seat: not found: {$id}");
        }

        return CampaignSeat::fromRow($row);
    }

    /**
     * @return list<CampaignSeat>
     */
    public function listCampaignSeats(string $campaignId): array
    {
        return array_map(
            static fn (object $row) => CampaignSeat::fromRow($row),
            DB::select(
                'SELECT id, campaign_id, seat_type, controller, controller_user_id,
                        actor_id, display_name, status, created_at, updated_at
                 FROM campaign_seats
                 WHERE campaign_id = ?
                 ORDER BY CASE seat_type WHEN \'dm\' THEN 0 ELSE 1 END, display_name',
                [$campaignId]
            )
        );
    }

    public function createPlayerSeat(string $campaignId, string $actorId, string $displayName): CampaignSeat
    {
        $actorId = trim($actorId);
        if ($actorId === '') {
            throw new InvalidArgumentException('actor_id required');
        }

        try {
            $entity = $this->getEntity($actorId);
        } catch (RuntimeException) {
            throw new InvalidArgumentException("actor not found: {$actorId}");
        }

        if ($entity->campaignId !== $campaignId) {
            throw new InvalidArgumentException('actor not in campaign');
        }

        if ($displayName === '') {
            $displayName = $entity->name;
        }

        return $this->createSeat(new CampaignSeat(
            id: '',
            campaignId: $campaignId,
            seatType: 'player',
            controller: 'ai',
            controllerUserId: null,
            actorId: $actorId,
            displayName: $displayName,
            status: 'active',
            createdAt: '',
            updatedAt: '',
        ));
    }

    public function assignSeatController(string $seatId, string $controller, ?string $userId): CampaignSeat
    {
        $controller = $this->normalizeController($controller);
        $this->getSeat($seatId);

        $userVal = null;
        if ($controller === 'human') {
            if ($userId === null || trim($userId) === '') {
                throw new InvalidArgumentException('controller_user_id required for human controller');
            }
            $userVal = trim($userId);
        }

        $updated = DB::update(
            'UPDATE campaign_seats
             SET controller = ?, controller_user_id = ?, updated_at = ?
             WHERE id = ?',
            [$controller, $userVal, $this->nowTimestamp(), $seatId]
        );

        if ($updated === 0) {
            throw new RuntimeException("seat not found: {$seatId}");
        }

        return $this->getSeat($seatId);
    }

    public function countCampaignSeats(string $campaignId): int
    {
        return (int) DB::scalar(
            'SELECT COUNT(*) FROM campaign_seats WHERE campaign_id = ?',
            [$campaignId]
        );
    }

    public function handoffSeat(
        string $seatId,
        string $controller,
        ?string $userId,
        string $reason,
        ?string $sessionId,
    ): SeatHandoffResult {
        $before = $this->getSeat($seatId);
        $seat = $this->assignSeatController($seatId, $controller, $userId);

        $summary = sprintf(
            'Seat %s (%s): %s → %s',
            $seat->id,
            $seat->displayName,
            $before->controller,
            $seat->controller
        );
        if (trim($reason) !== '') {
            $summary .= ' — '.trim($reason);
        }

        $meta = [
            'seat_id' => $seat->id,
            'from' => $before->controller,
            'to' => $seat->controller,
            'seat_type' => $seat->seatType,
            'display_name' => $seat->displayName,
        ];
        if ($before->actorId !== null) {
            $meta['actor_id'] = $before->actorId;
        }
        if ($seat->controllerUserId !== null) {
            $meta['controller_user_id'] = $seat->controllerUserId;
        }
        if (trim($reason) !== '') {
            $meta['reason'] = trim($reason);
        }

        $event = new Event(
            id: 'event_'.substr((string) Str::uuid(), 0, 8),
            campaignId: $seat->campaignId,
            sessionId: $sessionId,
            title: 'seat_handoff',
            summary: $summary,
            entityIds: json_encode($meta, JSON_THROW_ON_ERROR),
            createdAt: '',
        );
        $this->addEvent($event);

        if ($sessionId !== null && $seat->seatType === 'player' && $seat->controller === 'human') {
            try {
                $this->setFloorSeat($sessionId, $seat->id);
            } catch (RuntimeException) {
                // Best-effort, matching Go's ignored error.
            }
        }

        return new SeatHandoffResult($seat, $event);
    }

    public function releaseSeatToAI(string $seatId, string $reason, ?string $sessionId): SeatHandoffResult
    {
        if ($reason === '') {
            $reason = 'Released seat to AI control';
        }

        return $this->handoffSeat($seatId, 'ai', null, $reason, $sessionId);
    }

    public function initSessionFloor(string $sessionId, string $campaignId): SessionFloor
    {
        $floorSeatId = null;
        foreach ($this->listCampaignSeats($campaignId) as $seat) {
            if ($seat->seatType === 'player' && $seat->status === 'active') {
                $floorSeatId = $seat->id;
                break;
            }
        }

        DB::insert(
            'INSERT INTO session_floor (session_id, floor_seat_id, party_beat_queue, awaiting_player_checkpoint)
             VALUES (?, ?, ?, 0)',
            [$sessionId, $floorSeatId, '[]']
        );

        return $this->getSessionFloor($sessionId);
    }

    public function getSessionFloor(string $sessionId): SessionFloor
    {
        $row = DB::selectOne(
            'SELECT session_id, floor_seat_id, party_beat_queue, awaiting_player_checkpoint
             FROM session_floor WHERE session_id = ?',
            [$sessionId]
        );

        if ($row === null) {
            throw new RuntimeException("get session floor: not found: {$sessionId}");
        }

        $queue = json_decode((string) $row->party_beat_queue, true);
        if (! is_array($queue)) {
            $queue = [];
        }

        return new SessionFloor(
            sessionId: (string) $row->session_id,
            floorSeatId: $row->floor_seat_id !== null ? (string) $row->floor_seat_id : null,
            partyBeatQueue: array_values(array_map('strval', $queue)),
            awaitingPlayerCheckpoint: (int) $row->awaiting_player_checkpoint !== 0,
        );
    }

    /**
     * @param  list<string>  $queue
     */
    public function setSessionFloor(
        string $sessionId,
        ?string $floorSeatId,
        array $queue,
        bool $awaiting,
    ): SessionFloor {
        $queueRaw = json_encode($queue, JSON_THROW_ON_ERROR);

        $updated = DB::update(
            'UPDATE session_floor
             SET floor_seat_id = ?, party_beat_queue = ?, awaiting_player_checkpoint = ?
             WHERE session_id = ?',
            [$floorSeatId, $queueRaw, $awaiting ? 1 : 0, $sessionId]
        );

        if ($updated === 0) {
            throw new RuntimeException("session floor not found: {$sessionId}");
        }

        return $this->getSessionFloor($sessionId);
    }

    public function setFloorSeat(string $sessionId, string $seatId): SessionFloor
    {
        $seatId = trim($seatId);
        if ($seatId === '') {
            throw new InvalidArgumentException('floor_seat_id required');
        }

        $current = $this->getSessionFloor($sessionId);

        return $this->setSessionFloor(
            $sessionId,
            $seatId,
            $current->partyBeatQueue,
            $current->awaitingPlayerCheckpoint
        );
    }

    /**
     * @param  list<WorldChange>  $changes
     * @return list<ConflictWarning>
     */
    public function checkForConflicts(string $campaignId, array $changes): array
    {
        $warnings = [];
        $namesSeen = [];

        $entities = $this->listEntitiesByType($campaignId, 'npc', ReadScope::Dm);
        $entities = array_merge(
            $entities,
            $this->listEntitiesByType($campaignId, 'location', ReadScope::Dm),
            $this->listEntitiesByType($campaignId, 'faction', ReadScope::Dm),
        );

        foreach ($entities as $entity) {
            $namesSeen[strtolower($entity->name)] = $entity->id;
        }

        foreach ($changes as $change) {
            switch ($change->op) {
                case 'upsert_entity':
                case 'create_entity':
                    if ($change->entity === null) {
                        break;
                    }
                    $key = strtolower($change->entity->name);
                    if (
                        isset($namesSeen[$key])
                        && $namesSeen[$key] !== $change->entity->id
                    ) {
                        $warnings[] = new ConflictWarning(
                            'duplicate_name',
                            sprintf(
                                'Entity name %s already used by %s',
                                json_encode($change->entity->name),
                                $namesSeen[$key]
                            )
                        );
                    }
                    if ($change->entity->id !== '') {
                        try {
                            $existing = $this->getEntity($change->entity->id);
                            $oldData = json_decode($existing->data, true) ?: [];
                            $newData = json_decode($change->entity->data, true) ?: [];
                            $oldStatus = $oldData['status'] ?? null;
                            $newStatus = $newData['status'] ?? null;
                            if (
                                is_string($oldStatus)
                                && is_string($newStatus)
                                && $oldStatus !== $newStatus
                                && (
                                    ($oldStatus === 'dead' && $newStatus === 'alive')
                                    || ($oldStatus === 'alive' && $newStatus === 'dead')
                                )
                            ) {
                                $warnings[] = new ConflictWarning(
                                    'status_conflict',
                                    sprintf(
                                        '%s status changes from %s to %s',
                                        $change->entity->name,
                                        $oldStatus,
                                        $newStatus
                                    )
                                );
                            }
                        } catch (RuntimeException) {
                            // Entity does not exist yet.
                        }
                    }
                    break;

                case 'add_fact':
                    if ($change->fact === null || $change->fact->entityId === null) {
                        break;
                    }
                    $facts = $this->searchFacts($campaignId, '%', 100, ReadScope::Dm);
                    foreach ($facts as $fact) {
                        if ($fact->entityId !== $change->fact->entityId) {
                            continue;
                        }
                        if ($this->contradictsFact($fact->text, $change->fact->text)) {
                            $warnings[] = new ConflictWarning(
                                'possible_contradiction',
                                sprintf(
                                    'Proposed fact %s may contradict existing: %s',
                                    json_encode($change->fact->text),
                                    json_encode($fact->text)
                                )
                            );
                        }
                    }
                    break;
            }
        }

        return $warnings;
    }

    private function contradictsFact(string $existing, string $proposed): bool
    {
        $el = strtolower($existing);
        $pl = strtolower($proposed);

        if (str_contains($el, 'lost his left eye') && str_contains($pl, 'both eyes')) {
            return true;
        }
        if (str_contains($el, 'both eyes') && str_contains($pl, 'lost his left eye')) {
            return true;
        }
        if (str_contains($el, 'dead') && str_contains($pl, 'alive')) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<WorldChange>  $changes
     */
    public function proposeWorldUpdate(string $campaignId, array $changes, string $reason): PendingUpdate
    {
        $raw = json_encode(array_map(static fn (WorldChange $c) => $c->toArray(), $changes), JSON_THROW_ON_ERROR);
        $id = 'update_'.substr((string) Str::uuid(), 0, 8);

        DB::insert(
            "INSERT INTO pending_updates (id, campaign_id, proposed_changes, reason, status, created_at)
             VALUES (?, ?, ?, ?, 'pending', ?)",
            [$id, $campaignId, $raw, $reason, $this->nowTimestamp()]
        );

        return $this->getPendingUpdate($id);
    }

    public function getPendingUpdate(string $id): PendingUpdate
    {
        $row = DB::selectOne(
            'SELECT id, campaign_id, proposed_changes, reason, status, created_at
             FROM pending_updates WHERE id = ?',
            [$id]
        );

        if ($row === null) {
            throw new RuntimeException("get pending update: not found: {$id}");
        }

        return PendingUpdate::fromRow($row);
    }

    /**
     * @return list<PendingUpdate>
     */
    public function listPendingUpdates(string $campaignId): array
    {
        return array_map(
            static fn (object $row) => PendingUpdate::fromRow($row),
            DB::select(
                "SELECT id, campaign_id, proposed_changes, reason, status, created_at
                 FROM pending_updates
                 WHERE campaign_id = ? AND status = 'pending'
                 ORDER BY created_at DESC",
                [$campaignId]
            )
        );
    }

    public function rejectWorldUpdate(string $id, string $reason): void
    {
        $updated = DB::update(
            "UPDATE pending_updates SET status = 'rejected', reason = COALESCE(NULLIF(?, ''), reason)
             WHERE id = ? AND status = 'pending'",
            [$reason, $id]
        );

        if ($updated === 0) {
            throw new RuntimeException("pending update not found: {$id}");
        }
    }

    public function commitWorldUpdate(string $id): PendingUpdate
    {
        $update = $this->getPendingUpdate($id);
        if ($update->status !== 'pending') {
            throw new RuntimeException("update is not pending: {$update->status}");
        }

        /** @var list<array<string, mixed>> $rawChanges */
        $rawChanges = json_decode($update->proposedChanges, true, 512, JSON_THROW_ON_ERROR);
        $changes = array_map(static fn (array $row) => WorldChange::fromArray($row), $rawChanges);

        foreach ($changes as $change) {
            $this->applyChange($update->campaignId, $change);
        }

        DB::update(
            "UPDATE pending_updates SET status = 'committed' WHERE id = ?",
            [$id]
        );

        return $this->getPendingUpdate($id);
    }

    private function generateEntityId(string $type): string
    {
        $prefix = $type !== '' ? $type : 'entity';

        return $prefix.'_'.substr((string) Str::uuid(), 0, 8);
    }

    private function applyChange(string $campaignId, WorldChange $change): void
    {
        switch ($change->op) {
            case 'upsert_entity':
            case 'create_entity':
                if ($change->entity === null) {
                    throw new RuntimeException("entity required for {$change->op}");
                }
                $entity = $change->entity;
                $entityId = $entity->id !== '' ? $entity->id : $this->generateEntityId($entity->type);
                if ($entityId !== $entity->id || $entity->campaignId === '') {
                    $entity = new Entity(
                        id: $entityId,
                        campaignId: $entity->campaignId !== '' ? $entity->campaignId : $campaignId,
                        type: $entity->type,
                        name: $entity->name,
                        summary: $entity->summary,
                        data: $entity->data,
                        createdAt: $entity->createdAt,
                        updatedAt: $entity->updatedAt,
                    );
                }
                $this->upsertEntity($entity);
                break;

            case 'update_entity':
                if ($change->entityId === '' || $change->patch === '') {
                    throw new RuntimeException('entity_id and patch required for update_entity');
                }
                $this->patchEntity($change->entityId, $change->patch);
                break;

            case 'add_fact':
                if ($change->fact === null) {
                    throw new RuntimeException('fact required for add_fact');
                }
                $fact = $change->fact;
                if ($fact->campaignId === '') {
                    $fact = new Fact(
                        id: $fact->id,
                        campaignId: $campaignId,
                        entityId: $fact->entityId,
                        text: $fact->text,
                        visibility: $fact->visibility,
                        confidence: $fact->confidence,
                        sourceType: $fact->sourceType,
                        sourceId: $fact->sourceId,
                        createdAt: $fact->createdAt,
                    );
                }
                if ($fact->id === '') {
                    $fact = new Fact(
                        id: 'fact_'.substr((string) Str::uuid(), 0, 8),
                        campaignId: $fact->campaignId,
                        entityId: $fact->entityId,
                        text: $fact->text,
                        visibility: $fact->visibility,
                        confidence: $fact->confidence,
                        sourceType: $fact->sourceType,
                        sourceId: $fact->sourceId,
                        createdAt: $fact->createdAt,
                    );
                }
                $this->addFact($fact);
                break;

            case 'record_event':
                if ($change->event === null) {
                    throw new RuntimeException('event required for record_event');
                }
                $event = $change->event;
                if ($event->campaignId === '') {
                    $event = new Event(
                        id: $event->id,
                        campaignId: $campaignId,
                        sessionId: $event->sessionId,
                        title: $event->title,
                        summary: $event->summary,
                        entityIds: $event->entityIds,
                        createdAt: $event->createdAt,
                    );
                }
                if ($event->id === '') {
                    $event = new Event(
                        id: 'event_'.substr((string) Str::uuid(), 0, 8),
                        campaignId: $event->campaignId,
                        sessionId: $event->sessionId,
                        title: $event->title,
                        summary: $event->summary,
                        entityIds: $event->entityIds,
                        createdAt: $event->createdAt,
                    );
                }
                $this->addEvent($event);
                break;

            default:
                throw new RuntimeException("unknown change op: {$change->op}");
        }
    }

    /**
     * Patch an entity. The patch is a JSON object whose `name`/`summary` keys
     * update the entity columns; `data` (if an object) and every other key are
     * shallow-merged into the entity's `data` JSON. A flat patch such as
     * {"attitude_to_party": -25} therefore merges into `data` as expected.
     */
    public function patchEntity(string $id, string $patch): void
    {
        $entity = $this->getEntity($id);

        /** @var array<string, mixed> $fields */
        $fields = json_decode($patch, true, 512, JSON_THROW_ON_ERROR);

        $name = $entity->name;
        $summary = $entity->summary;

        /** @var array<string, mixed> $data */
        $data = json_decode($entity->data === '' ? '{}' : $entity->data, true, 512, JSON_THROW_ON_ERROR) ?: [];

        foreach ($fields as $key => $value) {
            if ($key === 'name') {
                $name = is_string($value) ? $value : (string) json_encode($value);
            } elseif ($key === 'summary') {
                $summary = is_string($value) ? $value : (string) json_encode($value);
            } elseif ($key === 'data') {
                $nested = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : $value;
                if (is_array($nested)) {
                    $data = array_merge($data, $nested);
                }
            } else {
                $data[$key] = $value;
            }
        }

        $this->upsertEntity(new Entity(
            id: $entity->id,
            campaignId: $entity->campaignId,
            type: $entity->type,
            name: $name,
            summary: $summary,
            data: json_encode($data, JSON_THROW_ON_ERROR),
            createdAt: $entity->createdAt,
            updatedAt: $this->nowTimestamp(),
        ));
    }

    private function normalizeSeatType(string $raw): string
    {
        $type = strtolower(trim($raw));

        return match ($type) {
            'dm', 'player' => $type,
            default => throw new InvalidArgumentException("invalid seat_type: {$raw}"),
        };
    }

    private function normalizeController(string $raw): string
    {
        $controller = strtolower(trim($raw));

        return match ($controller) {
            'human', 'ai' => $controller,
            default => throw new InvalidArgumentException("invalid controller: {$raw}"),
        };
    }

    private function normalizeSeatStatus(string $raw): string
    {
        if ($raw === '') {
            return 'active';
        }

        $status = strtolower(trim($raw));

        return match ($status) {
            'active', 'vacant', 'paused' => $status,
            default => throw new InvalidArgumentException("invalid status: {$raw}"),
        };
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
