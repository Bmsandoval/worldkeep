<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorldKeepClient;
use Illuminate\View\View;

class CampaignDashboardController extends Controller
{
    public function __construct(private readonly WorldKeepClient $worldkeep) {}

    public function __invoke(): View
    {
        $dashboard = $this->worldkeep->dashboard();

        return view('app.campaign.dashboard', [
            'dashboard' => $dashboard,
            'campaign' => $dashboard['campaign'] ?? [],
            'openSession' => $dashboard['open_session'] ?? null,
            'activePlots' => $dashboard['active_plots'] ?? [],
            'recentEvents' => $dashboard['recent_events'] ?? [],
            'pendingCount' => (int) ($dashboard['pending_update_count'] ?? 0),
            'continuityWarnings' => $dashboard['continuity_warnings'] ?? [],
            'scope' => $dashboard['scope'] ?? 'party',
        ]);
    }
}
