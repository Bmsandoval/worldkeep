<?php

namespace App\Providers;

use App\Services\WorldKeep\Engine;
use App\Services\WorldKeep\Mcp\Server as McpServer;
use App\Services\WorldKeep\Open5e\Client as Open5eClient;
use App\Services\WorldKeep\Store;
use App\Services\WorldKeepClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Store::class, function () {
            $store = new Store;
            $store->migrate();

            return $store;
        });

        $this->app->singleton(Engine::class, fn ($app) => new Engine(
            store: $app->make(Store::class),
            campaignId: (string) config('worldkeep.campaign_id'),
            role: (string) config('worldkeep.role', 'owner'),
        ));

        $this->app->singleton(Open5eClient::class, fn () => new Open5eClient(
            baseUrl: (string) config('worldkeep.open5e.base_url'),
            timeoutSeconds: (int) config('worldkeep.timeout_seconds', 15),
        ));

        $this->app->singleton(McpServer::class, fn ($app) => new McpServer(
            store: $app->make(Store::class),
            campaignId: (string) config('worldkeep.campaign_id'),
            role: (string) config('worldkeep.role', 'owner'),
            open5e: $app->make(Open5eClient::class),
        ));

        $this->app->singleton(WorldKeepClient::class, fn ($app) => new WorldKeepClient(
            engine: $app->make(Engine::class),
            campaignId: (string) config('worldkeep.campaign_id'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
