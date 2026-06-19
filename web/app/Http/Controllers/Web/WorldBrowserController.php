<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorldKeepClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorldBrowserController extends Controller
{
    public function __construct(private readonly WorldKeepClient $worldkeep) {}

    public function index(Request $request): View
    {
        $type = (string) $request->query('type', '');
        $query = trim((string) $request->query('q', ''));
        $entityTypes = config('worldkeep.entity_types', []);

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
        ]);
    }

    public function show(string $entityId): View
    {
        $entity = $this->worldkeep->getEntity($entityId);

        return view('app.campaign.world.show', [
            'entity' => $entity,
        ]);
    }
}
