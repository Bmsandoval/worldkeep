<?php

namespace App\Providers;

use App\Services\WorldKeepClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorldKeepClient::class, fn () => WorldKeepClient::fromConfig());
    }

    public function boot(): void
    {
        //
    }
}
