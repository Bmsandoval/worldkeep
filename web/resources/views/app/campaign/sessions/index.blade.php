@extends('layouts.app')

@section('title', 'Sessions — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Play sessions</h1>
            <p class="text-muted mb-0">Session timeline — events and entities touched.</p>
        </div>
        <a href="{{ route('app.campaign.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ph ph-gauge ph-icon"></i> Dashboard
        </a>
    </div>

    @forelse ($sessions as $session)
        <a href="{{ route('app.campaign.sessions.show', $session['id']) }}" class="card card-app mb-2 text-decoration-none text-body">
            <div class="card-body py-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div>
                        <div class="fw-semibold">{{ $session['title'] ?? $session['id'] }}</div>
                        <div class="text-muted small">Started {{ $session['started_at'] ?? '' }}</div>
                    </div>
                    <span class="badge {{ ($session['status'] ?? '') === 'open' ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $session['status'] ?? 'unknown' }}
                    </span>
                </div>
                @if (!empty($session['summary']))
                    <p class="small text-muted mb-0 mt-2">{{ $session['summary'] }}</p>
                @endif
            </div>
        </a>
    @empty
        <p class="text-muted">No sessions recorded yet.</p>
    @endforelse
@endsection
