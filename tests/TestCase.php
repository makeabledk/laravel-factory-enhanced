<?php

namespace Makeable\LaravelFactory\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\FactoryServiceProvider;

class TestCase extends BaseTestCase
{
    protected $connectionsToTransact = [
        'primary',
        'secondary',
    ];

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        /** @var \Illuminate\Foundation\Application $app */
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->register(FactoryServiceProvider::class);
        $app->useDatabasePath(__DIR__.'/database');

        $app['config']->set('database.default', 'primary');
        $app['config']->set('database.connections.primary', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('database.connections.secondary', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        return $app;
    }

    /**
     * @param  null  $class
     * @return Factory
     */
    protected function factory($class = null)
    {
        return Factory::factoryForModel($class);
    }
}
