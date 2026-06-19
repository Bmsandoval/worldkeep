@extends('layouts.app')

@section('title', 'Log in — WorldKeep')

@section('content')
    <div class="card card-app mx-auto" style="max-width: 24rem;">
        <div class="card-body p-4">
            <h1 class="h3 mb-4 d-flex align-items-center gap-2">
                <i class="ph ph-sign-in ph-icon"></i>
                Log in
            </h1>

            <form method="POST" action="{{ route('app.login') }}" class="vstack gap-3">
                @csrf
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        class="form-control"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                    >
                </div>
                <div>
                    <label for="password" class="form-label">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="form-control"
                        required
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn btn-app-primary w-100">
                    Log in
                </button>
            </form>

            <p class="text-muted small mt-4 mb-0">
                No account?
                <a href="{{ route('app.register') }}">Sign up</a>
            </p>
        </div>
    </div>
@endsection
