@extends('layouts.app')

@section('title', 'Sign up — WorldKeep')

@section('content')
    <div class="card card-app mx-auto" style="max-width: 24rem;">
        <div class="card-body p-4">
            <h1 class="h3 mb-4 d-flex align-items-center gap-2">
                <i class="ph ph-user-plus ph-icon"></i>
                Sign up
            </h1>

            <form method="POST" action="{{ route('app.register') }}" class="vstack gap-3">
                @csrf
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        class="form-control"
                        value="{{ old('name') }}"
                        required
                        autocomplete="name"
                    >
                </div>
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
                        autocomplete="new-password"
                    >
                </div>
                <div>
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        class="form-control"
                        required
                        autocomplete="new-password"
                    >
                </div>
                <button type="submit" class="btn btn-app-primary w-100">
                    Create account
                </button>
            </form>

            <p class="text-muted small mt-4 mb-0">
                Already have an account?
                <a href="{{ route('app.login') }}">Log in</a>
            </p>
        </div>
    </div>
@endsection
