<?php

namespace Tests\Unit\Support;

use App\Services\WorldKeep\Store;
use App\Support\UserUiPreferences;
use Illuminate\Contracts\Session\Session;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class UserUiPreferencesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_show_spoilers_requires_advanced_options(): void
    {
        $session = Mockery::mock(Session::class);
        $store = Mockery::mock(Store::class);
        $session->shouldReceive('get')->with('worldkeep.ui.advanced_options', false)->andReturn(true);
        $session->shouldReceive('get')->with('worldkeep.ui.show_spoilers', false)->andReturn(true);

        $prefs = new UserUiPreferences($session, $store);

        $this->assertSame('dm', $prefs->readScope());
    }

    public function test_show_spoilers_is_forced_off_when_advanced_disabled(): void
    {
        $session = Mockery::mock(Session::class);
        $store = Mockery::mock(Store::class);
        $session->shouldReceive('get')->with('worldkeep.ui.advanced_options', false)->andReturn(false);

        $prefs = new UserUiPreferences($session, $store);

        $this->assertFalse($prefs->showSpoilersEnabled());
        $this->assertSame('party', $prefs->readScope());
    }

    public function test_update_clears_spoilers_when_advanced_disabled(): void
    {
        $session = Mockery::mock(Session::class);
        $store = Mockery::mock(Store::class);
        $session->shouldReceive('put')->once()->with('worldkeep.ui.advanced_options', false);
        $session->shouldReceive('put')->once()->with('worldkeep.ui.show_spoilers', false);

        $prefs = new UserUiPreferences($session, $store);
        $prefs->update(false, true);
    }

    public function test_active_campaign_falls_back_when_session_value_missing(): void
    {
        $session = Mockery::mock(Session::class);
        $store = Mockery::mock(Store::class);
        $session->shouldReceive('get')->with('worldkeep.ui.active_campaign_id')->andReturn(null);

        $prefs = new UserUiPreferences($session, $store);

        $this->assertSame('campaign_001', $prefs->activeCampaignId());
    }

    public function test_active_campaign_falls_back_when_session_value_invalid(): void
    {
        $session = Mockery::mock(Session::class);
        $store = Mockery::mock(Store::class);
        $session->shouldReceive('get')->with('worldkeep.ui.active_campaign_id')->andReturn('missing_campaign');
        $store->shouldReceive('getCampaign')->once()->with('missing_campaign')->andThrow(new RuntimeException('not found'));

        $prefs = new UserUiPreferences($session, $store);

        $this->assertSame('campaign_001', $prefs->activeCampaignId());
    }
}
