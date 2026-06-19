@php
    $mobile = $mobile ?? false;
    $controls = $controls ?? ['campaign', 'spoilers'];
    $showCampaign = in_array('campaign', $controls, true);
    $showSpoilers = in_array('spoilers', $controls, true) && $sidebarAdvancedOptions;
@endphp

@if ($showCampaign || $showSpoilers)
<div @class(['sidebar-controls', 'sidebar-controls-mobile' => $mobile])>
    @if ($showCampaign)
        <form method="POST" action="{{ route('app.campaign.switch') }}" class="sidebar-control-form">
            @csrf
            <label class="sidebar-section-label" for="{{ $mobile ? 'mobile' : 'sidebar' }}-campaign">Campaign</label>
            <select name="campaign_id"
                    id="{{ $mobile ? 'mobile' : 'sidebar' }}-campaign"
                    class="form-select form-select-sm sidebar-select"
                    onchange="this.form.submit()">
                @foreach ($sidebarCampaigns as $campaign)
                    <option value="{{ $campaign->id }}" @selected($campaign->id === $sidebarActiveCampaignId)>
                        {{ $campaign->name }}
                    </option>
                @endforeach
            </select>
        </form>
    @endif

    @if ($showSpoilers)
        <form method="POST" action="{{ route('app.preferences.spoilers') }}" @class(['sidebar-control-form', 'mt-3' => $showCampaign])>
            @csrf
            <div class="form-check form-switch sidebar-spoilers-switch mb-0">
                <input class="form-check-input" type="checkbox" role="switch"
                       name="show_spoilers" value="1"
                       id="{{ $mobile ? 'mobile' : 'sidebar' }}_show_spoilers"
                       @checked($sidebarShowSpoilers)
                       onchange="this.form.submit()">
                <label class="form-check-label" for="{{ $mobile ? 'mobile' : 'sidebar' }}_show_spoilers">
                    Show spoilers
                </label>
            </div>
        </form>
    @endif
</div>
@endif
