@extends('layouts.dashboard')

@section('title', 'Home — WorldKeep')

@section('content')
    @php $user = auth()->user(); @endphp

    <header class="mb-4">
        <h1 class="h2 mb-1">Welcome back, {{ $user->name }}</h1>
        <p class="text-muted mb-0">Browse campaign canon, sessions, and world details.</p>
    </header>

    <div class="row g-3">
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('app.campaign.dashboard') }}" class="card shadow-sm h-100 text-decoration-none home-quick-card">
                <div class="card-body p-4">
                    <div class="home-quick-icon mb-3">
                        <i class="ph ph-gauge"></i>
                    </div>
                    <h2 class="h6 fw-semibold mb-1">Dashboard</h2>
                    <p class="text-muted small mb-0">Plots, events, and continuity warnings.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('app.campaign.world.index') }}" class="card shadow-sm h-100 text-decoration-none home-quick-card">
                <div class="card-body p-4">
                    <div class="home-quick-icon mb-3">
                        <i class="ph ph-globe-hemisphere-west"></i>
                    </div>
                    <h2 class="h6 fw-semibold mb-1">World</h2>
                    <p class="text-muted small mb-0">Browse and search campaign entities.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('app.campaign.sessions.index') }}" class="card shadow-sm h-100 text-decoration-none home-quick-card">
                <div class="card-body p-4">
                    <div class="home-quick-icon mb-3">
                        <i class="ph ph-clock-counter-clockwise"></i>
                    </div>
                    <h2 class="h6 fw-semibold mb-1">Sessions</h2>
                    <p class="text-muted small mb-0">Play session timeline and entity changes.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-3">
            <a href="{{ route('app.settings') }}" class="card shadow-sm h-100 text-decoration-none home-quick-card">
                <div class="card-body p-4">
                    <div class="home-quick-icon mb-3">
                        <i class="ph ph-gear"></i>
                    </div>
                    <h2 class="h6 fw-semibold mb-1">Settings</h2>
                    <p class="text-muted small mb-0">Advanced options and spoiler mode.</p>
                </div>
            </a>
        </div>
    </div>
@endsection
