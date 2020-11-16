<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\ServiceProvider;

class FactoryServiceProvider extends ServiceProvider
{
    public function register()
    {
//        $this->app->singleton(Factory::class, function ($app) {
//            return Factory::construct($app->make(Generator::class), $this->app->databasePath('factories'));
//        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            //            Factory::class,
        ];
    }
}
