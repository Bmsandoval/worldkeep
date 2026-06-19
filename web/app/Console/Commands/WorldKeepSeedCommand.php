<?php

namespace App\Console\Commands;

use App\Services\WorldKeep\Seed\Blackport;
use Illuminate\Console\Command;

class WorldKeepSeedCommand extends Command
{
    protected $signature = 'worldkeep:seed {campaign_id?}';

    protected $description = 'Seed the Blackport demo campaign (idempotent)';

    public function handle(Blackport $blackport): int
    {
        $campaignId = $this->argument('campaign_id') ?: Blackport::DEMO_CAMPAIGN_ID;
        $blackport->seed($campaignId);
        $this->info("Campaign {$campaignId} ready.");

        return self::SUCCESS;
    }
}
