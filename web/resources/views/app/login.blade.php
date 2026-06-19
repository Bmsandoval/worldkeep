@extends('layouts.auth')

@section('title', 'Log in — WorldKeep')

@section('content')
    <div class="auth-page-heading">
        <h1>Welcome back</h1>
        <p>Sign in with Cognito Hosted UI.</p>
    </div>

    @if (($cognitoConfigured ?? true) === false)
        <div class="alert alert-warning">
            Cognito is not configured. Set <code>COGNITO_*</code> variables in <code>.env</code>.
        </div>
    @else
        <a href="{{ route('app.login') }}" class="btn btn-primary w-100">
            Continue to sign in
        </a>
    @endif

    <p class="text-muted small text-center mt-4 mb-0">
        No account?
        <a href="{{ route('app.register') }}" class="fw-medium">Sign up</a>
    </p>
@endsection
