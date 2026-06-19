@extends('layouts.app')

@section('title', 'Home — WorldKeep')

@section('content')
    <header class="mb-4">
        <h1 class="h2 mb-2">WorldKeep</h1>
        <p class="text-muted mb-0">Persistent world intelligence for AI-assisted tabletop RPGs.</p>
    </header>

    <div class="card card-app">
        <div class="card-body p-4">
            @auth
                <p class="mb-3">
                    Signed in as <strong>{{ auth()->user()->name }}</strong>.
                    Review campaign health and approve canon updates from the dashboard.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('app.campaign.dashboard') }}" class="btn btn-app-primary btn-sm">
                        <i class="ph ph-gauge ph-icon"></i> Campaign dashboard
                    </a>
                    <a href="{{ route('app.campaign.approvals') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ph ph-check-square ph-icon"></i> Approval queue
                    </a>
                    <a href="{{ route('app.campaign.world.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ph ph-globe-hemisphere-west ph-icon"></i> World browser
                    </a>
                    <a href="{{ route('app.campaign.sessions.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ph ph-clock-counter-clockwise ph-icon"></i> Sessions
                    </a>
                </div>
                <p class="text-muted small mb-0">
                    Work queue: GitHub issues on <code>Bmsandoval/worldkeep</code> · Agent rules in <code>AGENTS.md</code>
                </p>
            @else
                <p class="mb-3">
                    Session auth is wired. <a href="{{ route('app.login') }}">Log in</a> or
                    <a href="{{ route('app.register') }}">sign up</a> to open the campaign dashboard.
                </p>
                <p class="text-muted small mb-0">
                    MCP and REST share one Go engine; this UI calls it server-side on the same host in production.
                </p>
            @endauth
        </div>
    </div>
@endsection
