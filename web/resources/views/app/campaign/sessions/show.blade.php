@extends('layouts.dashboard')

@section('title', ($session['title'] ?? 'Session').' — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $session['title'] ?? $session['id'] }}</h1>
            <p class="text-muted mb-0">
                <span class="badge {{ ($session['status'] ?? '') === 'open' ? 'text-bg-success' : 'text-bg-secondary' }}">
                    {{ $session['status'] ?? 'unknown' }}
                </span>
                · started {{ $session['started_at'] ?? '' }}
                @if (!empty($session['ended_at']))
                    · ended {{ $session['ended_at'] }}
                @endif
            </p>
        </div>
        <a href="{{ route('app.campaign.sessions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ph ph-clock-counter-clockwise ph-icon"></i> All sessions
        </a>
    </div>

    @if (!empty($session['summary']))
        <div class="card card-app mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted">Summary</h2>
                <p class="mb-0">{{ $session['summary'] }}</p>
            </div>
        </div>
    @endif

    @if (!empty($session['notes']))
        <div class="card card-app mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted">Notes</h2>
                <p class="mb-0">{{ $session['notes'] }}</p>
            </div>
        </div>
    @endif

    <section class="mb-4">
        <h2 class="h5">Events</h2>
        @forelse ($events as $event)
            <div class="border-bottom py-2">
                <div class="fw-semibold">{{ $event['title'] ?? $event['id'] ?? 'Event' }}</div>
                <div class="text-muted small">{{ $event['created_at'] ?? '' }}</div>
                <div>{{ $event['summary'] ?? '' }}</div>
            </div>
        @empty
            <p class="text-muted">No events in this session.</p>
        @endforelse
    </section>

    <section>
        <h2 class="h5">Entities modified</h2>
        @forelse ($modifiedEntities as $change)
            <div class="d-flex flex-wrap align-items-center gap-2 border-bottom py-2">
                <a href="{{ route('app.campaign.world.show', $change['entity_id']) }}">{{ $change['entity_id'] }}</a>
                <span class="badge text-bg-light">{{ $change['change_op'] ?? 'change' }}</span>
                <span class="text-muted small">{{ $change['created_at'] ?? '' }}</span>
            </div>
        @empty
            <p class="text-muted">No entity changes recorded for this session.</p>
        @endforelse
    </section>
@endsection
