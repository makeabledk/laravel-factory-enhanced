<?php

namespace Makeable\LaravelFactory;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Support\ServiceProvider;

class FactoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Up to laravel 7
        if (class_exists(EloquentFactory::class)) {
            $this->app->bind(EloquentFactory::class, Factory::class);
        }

        $this->app->singleton(Factory::class, function ($app) {
            return Factory::construct($app->make(Generator::class), $this->app->databasePath('factories'));
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            Factory::class,
        ];
    }
}
