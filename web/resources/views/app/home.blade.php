@extends('layouts.landing')

@section('title', 'WorldKeep — Persistent world intelligence for tabletop RPGs')

@section('content')

    <section class="lp-hero">
        <div class="lp-hero-inner">
            <div class="lp-eyebrow">Tabletop RPG canon</div>
            <h1 class="lp-headline">
                Keep the world<br>
                <span class="lp-accent">consistent</span>.
            </h1>
            <p class="lp-subheadline">
                Campaign dashboard, canon approvals, world browser, and session history —
                one place for GMs and AI agents to read and update shared lore.
            </p>
            <div class="lp-hero-actions">
                <a href="{{ route('app.register') }}" class="lp-btn-primary">
                    Get started
                    <i class="ph ph-arrow-right"></i>
                </a>
                <a href="{{ route('app.login') }}" class="lp-btn-ghost">Log in</a>
            </div>
        </div>
    </section>

    <section class="lp-features">
        <div class="lp-features-inner">
            <div class="lp-feature">
                <div class="lp-feature-icon">
                    <i class="ph ph-check-square"></i>
                </div>
                <h2>Canon approvals</h2>
                <p>Review proposed world updates before they land. Commit or reject with reasons and conflict warnings.</p>
            </div>
            <div class="lp-feature">
                <div class="lp-feature-icon">
                    <i class="ph ph-globe-hemisphere-west"></i>
                </div>
                <h2>World browser</h2>
                <p>Search entities, facts, and relationships. Browse NPCs, locations, factions, and plots from your campaign.</p>
            </div>
            <div class="lp-feature">
                <div class="lp-feature-icon">
                    <i class="ph ph-plugs-connected"></i>
                </div>
                <h2>MCP + REST</h2>
                <p>Agents use MCP tools; apps use REST. Laravel UI and Go engine share one host in production.</p>
            </div>
        </div>
    </section>

    <section class="lp-cta-band">
        <h2>Ready to run your campaign?</h2>
        <a href="{{ route('app.register') }}" class="lp-btn-primary">
            Sign in with Cognito
            <i class="ph ph-arrow-right"></i>
        </a>
    </section>

    <footer class="lp-footer">
        <span>© 2026 WorldKeep</span>
    </footer>

@endsection
