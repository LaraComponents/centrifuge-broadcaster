<?php

namespace LaraComponents\Centrifuge;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\ServiceProvider;
use LaraComponents\Centrifuge\Centrifuge;
use LaraComponents\Centrifuge\CentrifugeBroadcaster;

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
            return new CentrifugeBroadcaster($app['centrifuge']);
        });
    }

    public function register()
    {
        $this->app->singleton('centrifuge', function($app) {
            $config = $app['config']['broadcasting.connections.centrifuge'];
            $http = new HttpClient();
            $redis = $app['redis']->connection($config['redis_connection']);

            return new Centrifuge($config, $http, $redis);
        });

        $this->app->alias('centrifuge', 'LaraComponents\Centrifuge\Centrifuge');
        $this->app->alias('centrifuge', 'LaraComponents\Centrifuge\Contracts\Centrifuge');
    }
}
