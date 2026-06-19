@extends('layouts.auth')

@section('title', 'Signing in — WorldKeep')

@section('content')
    <div class="auth-page-heading text-center">
        @isset($error)
            <h1 class="h4 text-danger">Sign-in failed</h1>
            <p class="text-muted">{{ $error }}</p>
            <a href="{{ route('app.home') }}" class="btn btn-primary mt-3">Back home</a>
        @else
            <h1 class="h4">Processing authentication…</h1>
            <p class="text-muted mb-0">Please wait.</p>
        @endisset
    </div>
@endsection
