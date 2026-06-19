<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WorldKeepClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CanonApprovalsController extends Controller
{
    public function __construct(private readonly WorldKeepClient $worldkeep) {}

    public function index(): View
    {
        return view('app.campaign.approvals', [
            'pendingUpdates' => $this->worldkeep->listPendingUpdates(),
        ]);
    }

    public function commit(string $updateId): RedirectResponse
    {
        $this->worldkeep->commitUpdate($updateId);

        return redirect()->route('app.campaign.approvals')->with('status', 'Update committed to canon.');
    }

    public function reject(Request $request, string $updateId): RedirectResponse
    {
        $this->worldkeep->rejectUpdate($updateId, (string) $request->input('reason', ''));

        return redirect()->route('app.campaign.approvals')->with('status', 'Update rejected.');
    }
}
