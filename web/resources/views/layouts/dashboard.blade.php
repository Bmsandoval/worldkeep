<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'WorldKeep')</title>
    @include('partials.favicons')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1"></script>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="dash-body">
    <div class="app-shell">

        <nav class="app-topbar d-flex d-lg-none align-items-center justify-content-between">
            <a href="{{ route('app.home') }}" class="sidebar-brand-link text-decoration-none">
                <i class="ph ph-lightning"></i>
                <span>WorldKeep</span>
            </a>
            @auth
                <form method="POST" action="{{ route('app.logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-logout">
                        <i class="ph ph-sign-out"></i>
                    </button>
                </form>
            @endauth
        </nav>

        <aside class="app-sidebar d-none d-lg-flex flex-column">

            <div class="sidebar-brand">
                <a href="{{ route('app.home') }}" class="sidebar-brand-link text-decoration-none">
                    <i class="ph ph-lightning"></i>
                    <span>WorldKeep</span>
                </a>
            </div>

            @auth
                <nav class="sidebar-nav flex-grow-1">
                    <div class="sidebar-section-label">Campaign</div>
                    <a href="{{ route('app.campaign.dashboard') }}"
                       class="sidebar-nav-link {{ request()->routeIs('app.campaign.dashboard') ? 'active' : '' }}">
                        <i class="ph ph-gauge"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('app.campaign.approvals') }}"
                       class="sidebar-nav-link {{ request()->routeIs('app.campaign.approvals*') ? 'active' : '' }}">
                        <i class="ph ph-check-square"></i>
                        Approvals
                    </a>
                    <a href="{{ route('app.campaign.world.index') }}"
                       class="sidebar-nav-link {{ request()->routeIs('app.campaign.world.*') ? 'active' : '' }}">
                        <i class="ph ph-globe-hemisphere-west"></i>
                        World
                    </a>
                    <a href="{{ route('app.campaign.sessions.index') }}"
                       class="sidebar-nav-link {{ request()->routeIs('app.campaign.sessions.*') ? 'active' : '' }}">
                        <i class="ph ph-clock-counter-clockwise"></i>
                        Sessions
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <div class="sidebar-user">
                        <i class="ph ph-user-circle"></i>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('app.logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-logout">
                            <i class="ph ph-sign-out"></i>
                            Log out
                        </button>
                    </form>
                </div>
            @endauth

        </aside>

        <div class="app-content">
            @auth
                <nav class="app-mobile-nav d-lg-none">
                    <a href="{{ route('app.campaign.dashboard') }}"
                       class="app-mobile-nav-link {{ request()->routeIs('app.campaign.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('app.campaign.approvals') }}"
                       class="app-mobile-nav-link {{ request()->routeIs('app.campaign.approvals*') ? 'active' : '' }}">Approvals</a>
                    <a href="{{ route('app.campaign.world.index') }}"
                       class="app-mobile-nav-link {{ request()->routeIs('app.campaign.world.*') ? 'active' : '' }}">World</a>
                    <a href="{{ route('app.campaign.sessions.index') }}"
                       class="app-mobile-nav-link {{ request()->routeIs('app.campaign.sessions.*') ? 'active' : '' }}">Sessions</a>
                </nav>
            @endauth

            <main class="app-main">

                @if (session('status'))
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="status">
                        <i class="ph ph-check-circle ph-icon"></i>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mb-4" role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ph ph-warning ph-icon mt-1"></i>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
