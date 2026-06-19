@extends('layouts.dashboard')

@section('title', 'World browser — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">World browser</h1>
            <p class="text-muted mb-0">Browse campaign entities and search canon.</p>
        </div>
        <a href="{{ route('app.campaign.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ph ph-gauge ph-icon"></i> Dashboard
        </a>
    </div>

    @if ($showSpoilers ?? false)
        <div class="alert alert-warning py-2 px-3 small mb-4">
            <i class="ph ph-eye ph-icon"></i> Spoilers on — showing DM-only secrets and hidden facts.
        </div>
    @endif

    <form method="GET" action="{{ route('app.campaign.world.index') }}" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="search" name="q" value="{{ $searchQuery }}" class="form-control" placeholder="Search entities and facts…">
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-app-primary">Search</button>
            @if ($searchQuery !== '')
                <a href="{{ route('app.campaign.world.index', ['type' => $activeType]) }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </div>
    </form>

    @if ($searchQuery === '')
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="{{ route('app.campaign.world.index') }}"
               class="btn btn-sm {{ $activeType === '' ? 'btn-app-primary' : 'btn-outline-secondary' }}">All</a>
            @foreach ($entityTypes as $type)
                <a href="{{ route('app.campaign.world.index', ['type' => $type]) }}"
                   class="btn btn-sm {{ $activeType === $type ? 'btn-app-primary' : 'btn-outline-secondary' }}">
                    @if ($type === 'secret')
                        Secrets
                    @else
                        {{ ucfirst($type) }}s
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    @if ($searchQuery !== '' && count($facts) > 0)
        <section class="mb-4">
            <h2 class="h5">Matching facts</h2>
            @foreach ($facts as $fact)
                <div class="border-bottom py-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if (($fact['visibility'] ?? '') === 'dm_only')
                            <span class="badge text-bg-warning">Spoiler</span>
                        @endif
                    </div>
                    <div>{{ $fact['text'] ?? json_encode($fact) }}</div>
                    @if (!empty($fact['entity_id']))
                        <a href="{{ route('app.campaign.world.show', $fact['entity_id']) }}" class="small">{{ $fact['entity_id'] }}</a>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section>
        <h2 class="h5">{{ $searchQuery !== '' ? 'Matching entities' : 'Entities' }}</h2>
        @forelse ($entities as $entity)
            <a href="{{ route('app.campaign.world.show', $entity['id']) }}"
               class="card card-app mb-2 text-decoration-none text-body {{ ($entity['type'] ?? '') === 'secret' ? 'spoiler-card' : '' }}">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold">{{ $entity['name'] ?? $entity['id'] }}</div>
                            <div class="text-muted small">{{ $entity['summary'] ?? '' }}</div>
                        </div>
                        <span class="badge {{ ($entity['type'] ?? '') === 'secret' ? 'text-bg-warning' : 'text-bg-light' }} align-self-start">
                            {{ ($entity['type'] ?? '') === 'secret' ? 'Secret' : ($entity['type'] ?? 'entity') }}
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-muted">No entities found.</p>
        @endforelse
    </section>
@endsection
