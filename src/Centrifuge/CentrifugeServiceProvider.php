<?php

namespace LaraComponents\Centrifuge;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;

class CentrifugeServiceProvider extends ServiceProvider
{
    /**
     * Add centrifuge broadcaster
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifuge', function ($app, $config) {
            return new CentrifugeBroadcaster(
                new Centrifuge($config['endpoint'], $config['secret'])
            );
        });
    }
    /**
     * Register centrifuge services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('centrifuge', CentrifugeManager::class);
    }
}