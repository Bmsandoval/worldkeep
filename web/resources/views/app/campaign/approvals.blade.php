@extends('layouts.dashboard')

@section('title', 'Canon approvals — WorldKeep')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Canon approval queue</h1>
            <p class="text-muted mb-0">Review proposed world updates before they become canon.</p>
        </div>
        <a href="{{ route('app.campaign.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ph ph-gauge ph-icon"></i> Dashboard
        </a>
    </div>

    @forelse ($pendingUpdates as $item)
        @php($update = $item)
        <div class="card card-app mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <div>
                        <div class="fw-semibold">{{ $update['id'] ?? 'update' }}</div>
                        <div class="text-muted small">{{ $update['created_at'] ?? '' }}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('app.campaign.approvals.commit', $update['id']) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">Commit</button>
                        </form>
                        <form method="POST" action="{{ route('app.campaign.approvals.reject', $update['id']) }}" class="d-flex gap-1">
                            @csrf
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (optional)" style="min-width: 10rem;">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                        </form>
                    </div>
                </div>
                @if (!empty($update['reason']))
                    <p class="mb-2"><span class="text-muted">Reason:</span> {{ $update['reason'] }}</p>
                @endif
                @if (!empty($update['warnings']))
                    <div class="alert alert-warning py-2 mb-2">
                        <div class="fw-semibold small">Conflict warnings</div>
                        <ul class="mb-0 small">
                            @foreach ($update['warnings'] as $warning)
                                <li>{{ $warning['message'] ?? json_encode($warning) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <pre class="small bg-light p-2 rounded mb-0" style="max-height: 12rem; overflow: auto;">{{ json_encode($update['proposed_changes'] ?? [], JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @empty
        <p class="text-muted">No pending updates — canon queue is clear.</p>
    @endforelse
@endsection
