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
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-md navbar-app">
        <div class="container" style="max-width: 48rem;">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('app.home') }}">
                <i class="ph ph-lightning ph-icon fs-4"></i>
                WorldKeep
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-md-center gap-md-1">
                    @auth
                        <li class="nav-item">
                            <a class="nav-link nav-link-app" href="{{ route('app.campaign.dashboard') }}">
                                <i class="ph ph-gauge ph-icon"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-link-app" href="{{ route('app.campaign.approvals') }}">
                                <i class="ph ph-check-square ph-icon"></i> Approvals
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-link-app" href="{{ route('app.campaign.world.index') }}">
                                <i class="ph ph-globe-hemisphere-west ph-icon"></i> World
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-link-app" href="{{ route('app.campaign.sessions.index') }}">
                                <i class="ph ph-clock-counter-clockwise ph-icon"></i> Sessions
                            </a>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link text-muted mb-0 py-2">{{ auth()->user()->name }}</span>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('app.logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link-app text-decoration-none p-2">
                                    <i class="ph ph-sign-out ph-icon"></i> Log out
                                </button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link nav-link-app" href="{{ route('app.login') }}">
                                <i class="ph ph-sign-in ph-icon"></i> Log in
                            </a>
                        </li>
                        <li class="nav-item ms-md-2">
                            <a class="btn btn-app-primary btn-sm" href="{{ route('app.register') }}">
                                <i class="ph ph-user-plus ph-icon"></i> Sign up
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main class="container flex-grow-1 py-4 py-md-5" style="max-width: 48rem;">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center gap-2" role="status">
                <i class="ph ph-check-circle ph-icon"></i>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
