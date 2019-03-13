<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Makeable\LaravelFactory\Tests\Stubs\Customer;
use Makeable\LaravelFactory\Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_can_apply_a_state()
    {
        $customer = $this->factory(Customer::class)->state('happy')->create();

        $this->assertEquals(5, $customer->satisfaction);
    }

    /** @test **/
    public function it_filters_null_states()
    {
        $customer = $this->factory(Customer::class)->state(null)->create();

        $this->assertInstanceOf(Customer::class, $customer);
    }

//    /** @test **/
//    public function it_throws_exception_when_missing_state()
//    {
//        $this->expectException(InvalidArgumentException::class);
//
//        $this->factory(Customer::class)->state('foobar')->create();
//    }
}
