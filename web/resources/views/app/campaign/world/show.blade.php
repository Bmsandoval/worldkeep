@extends('layouts.dashboard')

@section('title', ($entity['name'] ?? 'Entity').' — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $entity['name'] ?? $entity['id'] }}</h1>
            <p class="text-muted mb-0">
                <span class="badge {{ ($entity['type'] ?? '') === 'secret' ? 'text-bg-warning' : 'text-bg-light' }}">{{ $entity['type'] ?? 'entity' }}</span>
                <code class="ms-1">{{ $entity['id'] ?? '' }}</code>
            </p>
        </div>
        <a href="{{ route('app.campaign.world.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ph ph-globe-hemisphere-west ph-icon"></i> World browser
        </a>
    </div>

    @if (($entity['type'] ?? '') === 'secret' && ($showSpoilers ?? false))
        <div class="alert alert-warning py-2 px-3 small mb-4">
            <i class="ph ph-eye ph-icon"></i> DM secret — normally hidden from party view.
        </div>
    @endif

    @if (!empty($entity['summary']))
        <div class="card card-app mb-4">
            <div class="card-body">
                <h2 class="h6 text-muted">Summary</h2>
                <p class="mb-0">{{ $entity['summary'] }}</p>
            </div>
        </div>
    @endif

    @if (!empty($entity['data']))
        <div class="card card-app">
            <div class="card-body">
                <h2 class="h6 text-muted">Data</h2>
                <pre class="small bg-light p-2 rounded mb-0" style="max-height: 20rem; overflow: auto;">{{ json_encode($entity['data'], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif
@endsection
