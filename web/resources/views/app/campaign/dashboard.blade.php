@extends('layouts.dashboard')

@section('title', 'Campaign dashboard — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Campaign dashboard</h1>
            <p class="text-muted mb-0">{{ $campaign['name'] ?? 'Campaign' }} · scope <code>{{ $scope }}</code></p>
        </div>
        <a href="{{ route('app.campaign.world.index') }}" class="btn btn-app-primary btn-sm">
            <i class="ph ph-globe-hemisphere-west ph-icon"></i> World browser
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Recent events</div>
                    @if (count($recentEvents) > 0)
                        <a href="#recent-events" class="display-6 text-decoration-none text-body d-block">{{ count($recentEvents) }}</a>
                    @else
                        <div class="display-6">{{ count($recentEvents) }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Continuity warnings</div>
                    <div class="display-6">{{ count($continuityWarnings) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Active plots</div>
                    @if (count($activePlots) > 0)
                        <a href="#active-plots" class="display-6 text-decoration-none text-body d-block">{{ count($activePlots) }}</a>
                    @else
                        <div class="display-6">{{ count($activePlots) }}</div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Open session</div>
                    <div class="fw-semibold">{{ $openSession['title'] ?? 'None' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (count($continuityWarnings) > 0)
        <section class="mb-4">
            <h2 class="h5">Continuity warnings</h2>
            <ul class="list-group">
                @foreach ($continuityWarnings as $warning)
                    <li class="list-group-item">
                        <strong>{{ $warning['type'] ?? 'warning' }}</strong>
                        — {{ $warning['message'] ?? json_encode($warning) }}
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section id="active-plots" class="mb-4">
        <h2 class="h5">Active plots</h2>
        @forelse ($activePlots as $plot)
            <div class="card card-app mb-2">
                <div class="card-body py-3">
                    <div class="fw-semibold">{{ $plot['name'] ?? $plot['id'] }}</div>
                    <div class="text-muted small">{{ $plot['summary'] ?? '' }}</div>
                </div>
            </div>
        @empty
            <p class="text-muted">No active plots.</p>
        @endforelse
    </section>

    @if (count($secrets ?? []) > 0)
        <section class="mb-4">
            <h2 class="h5">Secrets <span class="badge text-bg-warning">Spoilers</span></h2>
            @foreach ($secrets as $secret)
                <a href="{{ route('app.campaign.world.show', $secret['id']) }}" class="card card-app spoiler-card mb-2 text-decoration-none text-body">
                    <div class="card-body py-3">
                        <div class="fw-semibold">{{ $secret['name'] ?? $secret['id'] }}</div>
                        <div class="text-muted small">{{ $secret['summary'] ?? '' }}</div>
                    </div>
                </a>
            @endforeach
        </section>
    @endif

    <section id="recent-events">
        <h2 class="h5">Recent events</h2>
        @forelse ($recentEvents as $event)
            <div class="border-bottom py-2">
                <div class="small text-muted">{{ $event['occurred_at'] ?? $event['created_at'] ?? '' }}</div>
                <div>{{ $event['summary'] ?? $event['description'] ?? json_encode($event) }}</div>
            </div>
        @empty
            <p class="text-muted">No recent events.</p>
        @endforelse
    </section>
@endsection
