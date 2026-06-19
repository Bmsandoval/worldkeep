<?php

namespace App\Support;

use App\Services\WorldKeep\Data\Campaign;
use App\Services\WorldKeep\Store;
use Illuminate\Contracts\Session\Session;
use RuntimeException;

/**
 * Per-session UI preferences for the authenticated web app.
 */
final class UserUiPreferences
{
    private const SESSION_ADVANCED = 'worldkeep.ui.advanced_options';

    private const SESSION_SPOILERS = 'worldkeep.ui.show_spoilers';

    private const SESSION_CAMPAIGN = 'worldkeep.ui.active_campaign_id';

    public function __construct(
        private readonly Session $session,
        private readonly Store $store,
    ) {}

    public function advancedOptionsEnabled(): bool
    {
        return (bool) $this->session->get(self::SESSION_ADVANCED, false);
    }

    public function showSpoilersEnabled(): bool
    {
        if (! $this->advancedOptionsEnabled()) {
            return false;
        }

        return (bool) $this->session->get(self::SESSION_SPOILERS, false);
    }

    public function readScope(): string
    {
        return $this->showSpoilersEnabled() ? 'dm' : 'party';
    }

    public function activeCampaignId(): string
    {
        $default = (string) config('worldkeep.campaign_id');
        $stored = $this->session->get(self::SESSION_CAMPAIGN);

        if (! is_string($stored) || $stored === '') {
            return $default;
        }

        try {
            $this->store->getCampaign($stored);
        } catch (RuntimeException) {
            return $default;
        }

        return $stored;
    }

    public function activeCampaign(): Campaign
    {
        return $this->store->getCampaign($this->activeCampaignId());
    }

    public function setActiveCampaignId(string $campaignId): void
    {
        $this->store->getCampaign($campaignId);
        $this->session->put(self::SESSION_CAMPAIGN, $campaignId);
    }

    public function setAdvancedOptions(bool $enabled): void
    {
        $this->session->put(self::SESSION_ADVANCED, $enabled);

        if (! $enabled) {
            $this->session->put(self::SESSION_SPOILERS, false);
        }
    }

    public function setShowSpoilers(bool $enabled): void
    {
        if (! $this->advancedOptionsEnabled()) {
            $this->session->put(self::SESSION_SPOILERS, false);

            return;
        }

        $this->session->put(self::SESSION_SPOILERS, $enabled);
    }

    public function update(bool $advancedOptions, bool $showSpoilers): void
    {
        $this->setAdvancedOptions($advancedOptions);

        if ($advancedOptions) {
            $this->setShowSpoilers($showSpoilers);
        }
    }
}
