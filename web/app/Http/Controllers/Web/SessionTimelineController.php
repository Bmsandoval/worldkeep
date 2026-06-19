<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorldKeepClient;
use Illuminate\View\View;

class SessionTimelineController extends Controller
{
    public function __construct(private readonly WorldKeepClient $worldkeep) {}

    public function index(): View
    {
        return view('app.campaign.sessions.index', [
            'sessions' => $this->worldkeep->listSessions(),
        ]);
    }

    public function show(string $sessionId): View
    {
        $workspace = $this->worldkeep->getSession($sessionId);

        return view('app.campaign.sessions.show', [
            'session' => $workspace['session'] ?? [],
            'events' => $workspace['events'] ?? [],
            'modifiedEntities' => $workspace['modified_entities'] ?? [],
        ]);
    }
}
