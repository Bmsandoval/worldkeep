<?php

namespace App\Services\WorldKeep\Mcp;

use App\Services\WorldKeep\Mcp\Handlers\ContextHandler;
use App\Services\WorldKeep\Mcp\Handlers\DashboardHandler;
use App\Services\WorldKeep\Mcp\Handlers\HandoffHandler;
use App\Services\WorldKeep\Mcp\Handlers\MvpHandler;
use App\Services\WorldKeep\Mcp\Handlers\Open5eHandler;
use App\Services\WorldKeep\Mcp\Handlers\ReadHandler;
use App\Services\WorldKeep\Mcp\Handlers\RecordHandler;
use App\Services\WorldKeep\Mcp\Handlers\SeatsHandler;
use App\Services\WorldKeep\Mcp\Handlers\SessionHandler;
use App\Services\WorldKeep\Mcp\Handlers\WriteHandler;
use App\Services\WorldKeep\Open5e\Client as Open5eClient;
use App\Services\WorldKeep\Store;

class Server
{
    public string $activeSessionId = '';

    public function __construct(
        public readonly Store $store,
        public readonly string $campaignId,
        public readonly string $role = 'owner',
        public readonly ?Open5eClient $open5e = null,
        private readonly ?DashboardHandler $dashboardHandler = null,
        private readonly ?ReadHandler $readHandler = null,
        private readonly ?ContextHandler $contextHandler = null,
        private readonly ?WriteHandler $writeHandler = null,
        private readonly ?SessionHandler $sessionHandler = null,
        private readonly ?RecordHandler $recordHandler = null,
        private readonly ?MvpHandler $mvpHandler = null,
        private readonly ?SeatsHandler $seatsHandler = null,
        private readonly ?HandoffHandler $handoffHandler = null,
        private readonly ?Open5eHandler $open5eHandler = null,
    ) {}

    /**
     * @param  array<string, mixed>  $request
     * @return array{0: array<string, mixed>|null, 1: bool}
     */
    public function dispatch(array $request): array
    {
        $id = $request['id'] ?? null;
        $isNotification = $id === null;

        $method = (string) ($request['method'] ?? '');

        switch ($method) {
            case 'initialize':
                return [$this->reply($id, $this->initializeResult($request['params'] ?? null), null), ! $isNotification];
            case 'notifications/initialized':
            case 'notifications/cancelled':
                return [null, false];
            case 'ping':
                return [$this->reply($id, [], null), ! $isNotification];
            case 'tools/list':
                return [$this->reply($id, ['tools' => ToolDefinitions::all()], null), ! $isNotification];
            case 'tools/call':
                $params = is_array($request['params'] ?? null) ? $request['params'] : [];
                $name = (string) ($params['name'] ?? '');
                $arguments = $params['arguments'] ?? [];
                if (! is_array($arguments)) {
                    $decoded = json_decode((string) $arguments, true);
                    $arguments = is_array($decoded) ? $decoded : [];
                }

                [$result, $error] = $this->callTool($name, $arguments);

                return [$this->reply($id, $result, $error), ! $isNotification];
            default:
                return [$this->reply($id, null, Protocol::rpcError(
                    Protocol::CODE_METHOD_NOT_FOUND,
                    'method not found: '.$method,
                )), ! $isNotification];
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    public function callTool(string $name, array $args): array
    {
        $write = $this->writeHandler ?? new WriteHandler;

        return match ($name) {
            'get_campaign_dashboard' => $this->dashboard()->getCampaignDashboard($this, $args),
            'prepare_session_brief' => $this->dashboard()->prepareSessionBrief($this, $args),
            'get_campaign_overview' => $this->read()->getCampaignOverview($this, $args),
            'get_entity' => $this->read()->getEntity($this, $args),
            'search_world' => $this->read()->searchWorld($this, $args),
            'compile_scene_context' => $this->context()->compileSceneContext($this, $args),
            'get_recent_events' => $this->context()->getRecentEvents($this, $args),
            'get_active_plots' => $this->context()->getActivePlots($this, $args),
            'search_rulings' => $this->context()->searchRulings($this, $args),
            'propose_world_update' => $write->proposeWorldUpdate($this, $args),
            'list_pending_updates' => $write->listPendingUpdates($this, $args),
            'commit_world_update' => $write->commitWorldUpdate($this, $args),
            'reject_world_update' => $write->rejectWorldUpdate($this, $args),
            'check_for_conflicts' => $write->checkForConflicts($this, $args),
            'start_session' => $this->session()->startSession($this, $args),
            'get_session' => $this->session()->getSession($this, $args),
            'end_session' => $this->session()->endSession($this, $args),
            'record_event' => $this->record()->recordEvent($this, $args),
            'record_ruling' => $this->record()->recordRuling($this, $args),
            'create_secret' => $this->mvp($write)->createSecret($this, $args),
            'import_campaign_markdown' => $this->mvp($write)->importCampaignMarkdown($this, $args),
            'set_campaign_role' => $this->mvp($write)->setCampaignRole($this, $args),
            'list_campaign_seats' => $this->seats()->listCampaignSeats($this, $args),
            'get_seat' => $this->seats()->getSeat($this, $args),
            'create_player_seat' => $this->seats()->createPlayerSeat($this, $args),
            'assign_seat_controller' => $this->seats()->assignSeatController($this, $args),
            'handoff_seat' => $this->handoff()->handoffSeat($this, $args),
            'release_seat_to_ai' => $this->handoff()->releaseSeatToAI($this, $args),
            'get_session_floor' => $this->handoff()->getSessionFloor($this, $args),
            'search_rules_reference' => $this->open5eTools()->searchRulesReference($this, $args),
            'get_rules_section' => $this->open5eTools()->getRulesSection($this, $args),
            'search_spells' => $this->open5eTools()->searchSpells($this, $args),
            'get_spell' => $this->open5eTools()->getSpell($this, $args),
            'search_creatures' => $this->open5eTools()->searchCreatures($this, $args),
            'get_creature' => $this->open5eTools()->getCreature($this, $args),
            'get_condition' => $this->open5eTools()->getCondition($this, $args),
            default => [Protocol::toolResultError('tool not implemented yet: '.$name), null],
        };
    }

    public function runStdio(): int
    {
        $stdin = fopen('php://stdin', 'r');
        if ($stdin === false) {
            return 1;
        }

        while (($line = fgets($stdin)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $request = json_decode($line, true);
            if (! is_array($request)) {
                $this->writeStdout([
                    'jsonrpc' => '2.0',
                    'error' => Protocol::rpcError(Protocol::CODE_PARSE_ERROR, 'parse error'),
                ]);

                continue;
            }

            [$response, $shouldReply] = $this->dispatch($request);
            if ($shouldReply && $response !== null) {
                $this->writeStdout($response);
            }
        }

        return 0;
    }

    /**
     * @param  mixed  $params
     * @return array<string, mixed>
     */
    private function initializeResult(mixed $params): array
    {
        $version = Protocol::DEFAULT_PROTOCOL_VERSION;
        if (is_array($params) && ! empty($params['protocolVersion'])) {
            $version = (string) $params['protocolVersion'];
        }

        return [
            'protocolVersion' => $version,
            'capabilities' => ['tools' => new \stdClass],
            'serverInfo' => ['name' => 'worldkeep', 'version' => '0.1.0'],
            'instructions' => Instructions::SERVER,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $result
     * @param  array<string, mixed>|null  $error
     * @return array<string, mixed>
     */
    private function reply(mixed $id, ?array $result, ?array $error): array
    {
        $response = ['jsonrpc' => '2.0'];
        if ($id !== null) {
            $response['id'] = $id;
        }
        if ($error !== null) {
            $response['error'] = $error;
        } else {
            $response['result'] = $result;
        }

        return $response;
    }

    /** @param  array<string, mixed>  $payload */
    private function writeStdout(array $payload): void
    {
        fwrite(STDOUT, json_encode($payload, JSON_UNESCAPED_UNICODE)."\n");
    }

    private function dashboard(): DashboardHandler
    {
        return $this->dashboardHandler ?? new DashboardHandler;
    }

    private function read(): ReadHandler
    {
        return $this->readHandler ?? new ReadHandler;
    }

    private function context(): ContextHandler
    {
        return $this->contextHandler ?? new ContextHandler;
    }

    private function session(): SessionHandler
    {
        return $this->sessionHandler ?? new SessionHandler;
    }

    private function record(): RecordHandler
    {
        return $this->recordHandler ?? new RecordHandler;
    }

    private function mvp(WriteHandler $write): MvpHandler
    {
        return $this->mvpHandler ?? new MvpHandler($write);
    }

    private function seats(): SeatsHandler
    {
        return $this->seatsHandler ?? new SeatsHandler;
    }

    private function handoff(): HandoffHandler
    {
        return $this->handoffHandler ?? new HandoffHandler;
    }

    private function open5eTools(): Open5eHandler
    {
        return $this->open5eHandler ?? new Open5eHandler;
    }
}
