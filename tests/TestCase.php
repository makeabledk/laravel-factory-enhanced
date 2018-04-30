<?php

namespace Makeable\LaravelFactory\Tests;

use App\User;
use Faker\Generator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\FactoryServiceProvider;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Division;

class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->register(FactoryServiceProvider::class);
        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__.'/migrations/');
        });

        $this->factory()->define(Company::class, function (Generator $faker) {
            return [
                'name' => $faker->company
            ];
        });

        $this->factory()->define(Division::class, function (Generator $faker) {
            return [
                'name' => $faker->company
            ];
        });

        return $app;
    }

    /**
     * @param null $class
     * @return Factory | FactoryBuilder
     */
    protected function factory($class = null)
    {
        $factory = app(Factory::class);

        if ($class) {
            return $factory->of($class);
        }

        return $factory;
    }

    /**
     * @return User
     */
    protected function user()
    {
        return factory(User::class)->create();
    }
}
