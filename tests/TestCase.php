<?php

namespace LaraComponents\Centrifuge\Test;

use Orchestra\Testbench\TestCase as Orchestra;
use LaraComponents\Centrifuge\CentrifugeServiceProvider;

class TestCase extends Orchestra
{
    /**
     * @var \LaraComponents\Centrifuge\Centrifuge
     */
    protected $centrifuge;

    public function setUp()
    {
        parent::setUp();
        $this->centrifuge = $this->app->make('centrifuge');
    }

    protected function getPackageProviders($app)
    {
        return [
            CentrifugeServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('broadcasting.default', 'centrifuge');
        $app['config']->set('broadcasting.connections.centrifuge', [
            'driver' => 'centrifuge',
            'secret' => 'f95bf295-bee6-4259-8912-0a58f4ecd30e',
            'url' => 'http://localhost:8000',
            'redis_api' => false,
            'redis_connection' => 'default',
        ]);
    }
}