<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\UserUiPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SidebarPreferencesController extends Controller
{
    public function __construct(private readonly UserUiPreferences $preferences) {}

    public function switchCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'string'],
        ]);

        try {
            $this->preferences->setActiveCampaignId($validated['campaign_id']);
        } catch (RuntimeException) {
            return back()->withErrors([
                'campaign_id' => 'Campaign not found.',
            ]);
        }

        return back()->with('status', 'Campaign switched.');
    }

    public function updateSpoilers(Request $request): RedirectResponse
    {
        if (! $this->preferences->advancedOptionsEnabled()) {
            abort(403);
        }

        $enabled = $request->boolean('show_spoilers');
        $this->preferences->setShowSpoilers($enabled);

        return back()->with(
            'status',
            $enabled ? 'Spoilers on — DM secrets visible.' : 'Spoilers hidden.',
        );
    }
}
