@extends('layouts.auth')

@section('title', 'Sign up — WorldKeep')

@section('content')
    <div class="auth-page-heading">
        <h1>Create your account</h1>
        <p>Register through Cognito Hosted UI.</p>
    </div>

    @if (($cognitoConfigured ?? true) === false)
        <div class="alert alert-warning">
            Cognito is not configured. Set <code>COGNITO_*</code> variables in <code>.env</code>.
        </div>
    @else
        @php
            $inviteCode = request('invite_code');
            $registerUrl = $inviteCode
                ? route('app.register', ['invite_code' => $inviteCode])
                : route('app.register');
        @endphp
        <a href="{{ $registerUrl }}" class="btn btn-primary w-100">
            Continue to sign up
        </a>
    @endif

    <p class="text-muted small text-center mt-4 mb-0">
        Already have an account? <a href="{{ route('app.login') }}" class="fw-medium">Log in</a>
    </p>
@endsection
