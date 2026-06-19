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
<body class="auth-page">
    <div class="auth-split">

        <aside class="auth-panel-brand d-none d-lg-flex flex-column justify-content-between">
            <a href="{{ route('app.home') }}" class="auth-brand-logo text-decoration-none d-flex align-items-center gap-2">
                <i class="ph ph-lightning"></i>
                <span>WorldKeep</span>
            </a>

            <div>
                <h2 class="auth-brand-headline">
                    Campaign canon, approvals, and session history in one place.
                </h2>
                <ul class="list-unstyled vstack gap-4 mb-0">
                    <li class="d-flex align-items-start gap-3">
                        <i class="ph ph-check-square auth-feature-icon"></i>
                        <div>
                            <div class="fw-semibold">Canon approvals</div>
                            <div class="auth-feature-sub">Review and commit proposed world changes before they land.</div>
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-3">
                        <i class="ph ph-globe-hemisphere-west auth-feature-icon"></i>
                        <div>
                            <div class="fw-semibold">World browser</div>
                            <div class="auth-feature-sub">Explore entities, facts, and relationships from your campaign.</div>
                        </div>
                    </li>
                    <li class="d-flex align-items-start gap-3">
                        <i class="ph ph-key auth-feature-icon"></i>
                        <div>
                            <div class="fw-semibold">Cognito Hosted UI</div>
                            <div class="auth-feature-sub">Sign in securely with the same auth stack as other hub apps.</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="auth-brand-footer">© 2026 WorldKeep</div>
        </aside>

        <main class="auth-panel-form">
            <div class="auth-form-inner">

                <a href="{{ route('app.home') }}" class="auth-mobile-logo d-flex d-lg-none align-items-center gap-2 text-decoration-none mb-5">
                    <i class="ph ph-lightning fs-4"></i>
                    <span>WorldKeep</span>
                </a>

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
            </div>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
