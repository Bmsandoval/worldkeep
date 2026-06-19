<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorldKeepClient;
use App\Support\UserUiPreferences;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorldBrowserController extends Controller
{
    public function __construct(
        private readonly WorldKeepClient $worldkeep,
        private readonly UserUiPreferences $preferences,
    ) {}

    public function index(Request $request): View
    {
        $type = (string) $request->query('type', '');
        $query = trim((string) $request->query('q', ''));
        $entityTypes = config('worldkeep.entity_types', []);
        if ($this->preferences->showSpoilersEnabled()) {
            $entityTypes = array_values(array_unique([...$entityTypes, 'secret']));
        }

        if ($query !== '') {
            $search = $this->worldkeep->searchWorld($query);
            $entities = $search['entities'] ?? [];
            $facts = $search['facts'] ?? [];
        } else {
            $entities = $this->worldkeep->listEntities($type !== '' ? $type : null);
            $facts = [];
            $search = null;
        }

        return view('app.campaign.world.index', [
            'entities' => $entities,
            'facts' => $facts,
            'entityTypes' => $entityTypes,
            'activeType' => $type,
            'searchQuery' => $query,
            'searchMeta' => $search,
            'showSpoilers' => $this->preferences->showSpoilersEnabled(),
        ]);
    }

    public function show(string $entityId): View
    {
        $entity = $this->worldkeep->getEntity($entityId);

        if (($entity['campaign_id'] ?? '') !== $this->worldkeep->activeCampaignId()) {
            abort(404);
        }

        return view('app.campaign.world.show', [
            'entity' => $entity,
            'showSpoilers' => $this->preferences->showSpoilersEnabled(),
        ]);
    }
}
