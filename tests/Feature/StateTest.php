<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Makeable\LaravelFactory\Tests\Stubs\Company;
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

    /** @test **/
    public function it_throws_exception_when_missing_state()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->factory(Customer::class)->state('foobar')->create();
    }

    /** @test **/
    public function multiple_states_can_be_passed_for_relations_inline_individually()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'active', 'flagship', 'divisions')
            ->create();

        $this->assertEquals(1, $company->divisions->count());
        $this->assertEquals(1, $company->divisions->first()->active);
        $this->assertEquals(1, $company->divisions->first()->flagship);
    }

    /** @test **/
    public function multiple_states_can_be_passed_for_relations_inline_as_array()
    {
        $company = $this->factory(Company::class)
            ->with(1, ['active', 'flagship'], 'divisions')
            ->create();

        $this->assertEquals(1, $company->divisions->count());
        $this->assertEquals(1, $company->divisions->first()->active);
        $this->assertEquals(1, $company->divisions->first()->flagship);
    }

    /** @test **/
    public function regression_states_works_with_nested_relations()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'active', 'divisions')
            ->with(2, 'divisions.employees')
            ->create();

        $this->assertEquals(1, $company->divisions->count());
        $this->assertEquals(2, $company->divisions->first()->employees->count());
        $this->assertEquals(1, $company->divisions->first()->active);
    }
}
