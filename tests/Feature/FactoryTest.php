<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Makeable\LaravelFactory\Tests\Stubs\Customer;
use Makeable\LaravelFactory\Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    function creating_models_with_no_relations()
    {
        $this->assertInstanceOf(User::class, $this->factory(User::class)->create());
    }

    /** @test **/
    function creating_models_without_prior_definitions()
    {
        $this->assertInstanceOf(Customer::class, $this->factory(Customer::class)->create());
    }

    /** @test **/
    function it_applies_closures_when_a_condition_is_met()
    {
        $createTwice = function ($builder) {
            $builder->times(2);
        };

        $this->assertInstanceOf(User::class,
            $this->factory(User::class)->when(false, $createTwice)->create()
        );

        $this->assertInstanceOf(Collection::class,
            $this->factory(User::class)->when(true, $createTwice)->create()
        );
    }

    /** @test **/
    function a_builder_can_be_tapped()
    {
        $createTwice = function ($builder) {
            $builder->times(2);
        };

        $this->assertInstanceOf(Collection::class,
            $this->factory(User::class)->tap($createTwice)->create()
        );
    }
}
