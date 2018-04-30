<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Division;
use Makeable\LaravelFactory\Tests\Stubs\Simple;
use Makeable\LaravelFactory\Tests\Stubs\UserFactory;
use Makeable\LaravelFactory\Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    //        dd($this->user());


//        dd($this->factory()->of(User::class)->create());
//
//        $factory = Factory::make(User::class)
//            ->with(3, 'active', 'companies.divisions');
//
//        dd($factory->relations);
//            ->create();


    /** @test **/
    function it_creates_models_with_no_relations()
    {
        $this->assertInstanceOf(User::class, $this->factory(User::class)->create());
    }

    /** @test **/
    function it_creates_models_even_without_prior_definitions()
    {
        $this->assertInstanceOf(Simple::class, $this->factory(Simple::class)->create());
    }

    function syntax()
    {
        UserFactory::make()
            ->with(3, 'active', 'companies')
            ->create();

        Company::make()
            ->with('owner')
            ->with(2, 'divisions')
            ->with(3, 'divisions.employees')
            ->create();

        $company = Company::make(['name' => 'Makeable'])
            ->with('owner')
            ->with(3, 'divisions', function (Division $factory) {
                $factory
                    ->fill(['name' => 'Makeable - '.$factory->faker()->city])
                    ->state('active')
                    ->with(3, 'employees');
            })
            ->create();

        Division::make()
            ->with('company', $company) // bind to existing
            ->create();

        Division::make()
            ->with('company') // new company
            ->create();
    }

}
