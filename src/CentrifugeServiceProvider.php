<?php

namespace LaraComponents\Centrifuge;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;

class CentrifugeServiceProvider extends ServiceProvider
{
    /**
     * Add centrifuge broadcaster.
     *
     * @param \Illuminate\Broadcasting\BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifuge', function ($app, $config) {
            return new CentrifugeBroadcaster($app['centrifuge']);
        });
    }

    public function register()
    {
        $this->app->singleton('centrifuge', function ($app) {
            $config = $app['config']['broadcasting.connections.centrifuge'];
            $http = new HttpClient();
            $redis = $app['redis']->connection($config['redis_connection']);

            return new Centrifuge($config, $http, $redis);
        });

        $this->app->alias('centrifuge', 'LaraComponents\Centrifuge\Centrifuge');
        $this->app->alias('centrifuge', 'LaraComponents\Centrifuge\Contracts\Centrifuge');
    }
}
