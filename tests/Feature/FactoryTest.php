<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Faker\Generator;
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

    /** @test **/
    public function regression_it_passes_inline_attributes_to_definitions()
    {
        $factory = $this->factory();
        $factory->defineAs(Customer::class, 'special', function (Generator $faker, array $attributes) {
            $this->assertEquals('bar', $attributes['foo']);
            return [];
        });

        $this->assertEquals('bar', $factory
            ->of(Customer::class, 'special')
            ->make(['foo' => 'bar'])
            ->foo
        );

        unset($factory[Customer::class]['special']);
    }
}
