<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\UserUiPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly UserUiPreferences $preferences) {}

    public function edit(): View
    {
        return view('app.settings', [
            'advancedOptions' => $this->preferences->advancedOptionsEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->preferences->setAdvancedOptions($request->boolean('advanced_options'));

        return redirect()
            ->route('app.settings')
            ->with('status', 'Settings saved.');
    }
}
