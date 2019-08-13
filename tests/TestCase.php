<?php

namespace Makeable\LaravelFactory\Tests;

use App\User;
use Faker\Generator;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\FactoryServiceProvider;
use Makeable\LaravelFactory\StateManager;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Customer;
use Makeable\LaravelFactory\Tests\Stubs\Department;

class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->register(FactoryServiceProvider::class);
        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__.'/migrations/');
        });

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

        $app->singleton(StateManager::class);

        // Make tests faster!
        Hash::setRounds(4);

        $this->setupModelFactories();

        return $app;
    }

    protected function setupModelFactories()
    {
        $this->factory()->define(Company::class, function (Generator $faker) {
            return ['name' => $faker->company];
        });
        $this->factory()->preset(Company::class, 'startup', function (FactoryBuilder $company, Generator $faker) {
            $company->with(1, 'departments')->with(1, 'departments.employees');
        });

        $this->factory()->state(Customer::class, 'happy', ['satisfaction' => 5]);

        $this->factory()->define(Department::class, function (Generator $faker) {
            return ['name' => $faker->company];
        });
        $this->factory()->state(Department::class, 'active', ['active' => 1]);
        $this->factory()->state(Department::class, 'flagship', ['flagship' => 1]);
        $this->factory()->preset(Department::class, 'mediumSized', function (FactoryBuilder $department, Generator $faker) {
            $department->with(1, 'manager')->with(4, 'employees');
        });
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
