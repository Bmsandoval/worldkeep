@extends('layouts.dashboard')

@section('title', 'Campaign dashboard — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Campaign dashboard</h1>
            <p class="text-muted mb-0">{{ $campaign['name'] ?? 'Campaign' }} · scope <code>{{ $scope }}</code></p>
        </div>
        <a href="{{ route('app.campaign.approvals') }}" class="btn btn-app-primary btn-sm">
            <i class="ph ph-check-square ph-icon"></i> Approvals
            @if ($pendingCount > 0)
                <span class="badge text-bg-light ms-1">{{ $pendingCount }}</span>
            @endif
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Pending approvals</div>
                    <div class="display-6">{{ $pendingCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Continuity warnings</div>
                    <div class="display-6">{{ count($continuityWarnings) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card card-app h-100">
                <div class="card-body">
                    <div class="text-muted small">Active plots</div>
                    <div class="display-6">{{ count($activePlots) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
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

    <section class="mb-4">
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

    <section>
        <h2 class="h5">Recent events</h2>
        @forelse ($recentEvents as $event)
            <div class="border-bottom py-2">
                <div class="small text-muted">{{ $event['occurred_at'] ?? '' }}</div>
                <div>{{ $event['summary'] ?? $event['description'] ?? json_encode($event) }}</div>
            </div>
        @empty
            <p class="text-muted">No recent events.</p>
        @endforelse
    </section>
@endsection
