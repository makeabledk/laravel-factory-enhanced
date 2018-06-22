<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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


//
//    /** @test **/
//    public function it_applies_states()
//    {
//
//    }

}
