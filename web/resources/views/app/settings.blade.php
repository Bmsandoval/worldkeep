@extends('layouts.dashboard')

@section('title', 'Settings — WorldKeep')

@section('content')
    <div class="mb-4">
        <h1 class="h3 mb-1">Settings</h1>
        <p class="text-muted mb-0">Preferences for your WorldKeep browser session.</p>
    </div>

    <div class="card card-app mw-form">
        <div class="card-body">
            <form method="POST" action="{{ route('app.settings.update') }}">
                @csrf
                @method('PUT')

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="advanced_options" value="1" id="advanced_options"
                           @checked($advancedOptions)>
                    <label class="form-check-label" for="advanced_options">
                        <span class="fw-semibold">Enable advanced options</span>
                        <div class="text-muted small">
                            Unlock power-user controls in the sidebar, including the spoilers toggle.
                        </div>
                    </label>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-app-primary">Save settings</button>
                </div>
            </form>
        </div>
    </div>
@endsection
