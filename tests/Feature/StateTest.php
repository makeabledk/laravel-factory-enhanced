<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Customer;
use Makeable\LaravelFactory\Tests\Stubs\User;
use Makeable\LaravelFactory\Tests\TestCase;

class StateTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_can_apply_a_state()
    {
        $customer = Customer::factory()->happy()->create();

        $this->assertEquals(5, $customer->satisfaction);
    }

    /** @test **/
    public function it_filters_null_args()
    {
        $customer = Customer::factory()->apply(null)->create();

        $this->assertInstanceOf(Customer::class, $customer);
    }

    /** @test **/
    public function it_throws_exception_when_missing_state()
    {
        $this->expectException(\BadMethodCallException::class);

        Customer::factory()->foobar()->create();
    }

    /** @test **/
    public function multiple_states_can_be_passed_for_relations_inline_individually()
    {
        $company = Company::factory()
            ->with(1, 'active', 'flagship', 'departments')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->active);
        $this->assertEquals(1, $company->departments->first()->flagship);
    }

    /** @test **/
    public function multiple_states_can_be_passed_for_relations_inline_as_array()
    {
        $company = Company::factory()
            ->with(1, ['active', 'flagship'], 'departments')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->active);
        $this->assertEquals(1, $company->departments->first()->flagship);
    }

    /** @test **/
    public function it_can_apply_what_was_formerly_know_as_a_preset()
    {
        $company = Company::factory()->startup()->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->employees->count());
    }

    /** @test **/
    public function presets_can_be_passed_for_relations_inline()
    {
        $company = Company::factory()
            ->with(1, 'mediumSized', 'departments')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(4, $company->departments->first()->employees->count());
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
    }

    /** @test **/
    public function regression_states_works_with_nested_relations()
    {
        $company = Company::factory()
            ->with(1, 'active', 'departments')
            ->with(2, 'departments.employees')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(2, $company->departments->first()->employees->count());
        $this->assertEquals(1, $company->departments->first()->active);
    }

    /** @test **/
    public function sequence_accepts_state_names()
    {
        $customer = Customer::factory()->sequence('happy', 'unhappy', ['satisfaction' => 3]);

        $this->assertEquals(5, $customer->make()->satisfaction);
        $this->assertEquals(1, $customer->make()->satisfaction);
        $this->assertEquals(3, $customer->make()->satisfaction);
    }
}
