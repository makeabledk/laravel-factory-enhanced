<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
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
            ->with(1, 'active', 'flagship', 'departments')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->active);
        $this->assertEquals(1, $company->departments->first()->flagship);
    }

    /** @test **/
    public function multiple_states_can_be_passed_for_relations_inline_as_array()
    {
        $company = $this->factory(Company::class)
            ->with(1, ['active', 'flagship'], 'departments')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->active);
        $this->assertEquals(1, $company->departments->first()->flagship);
    }

    /** @test **/
    public function it_can_apply_a_preset()
    {
        $company = $this->factory(Company::class)->preset('startup')->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->employees->count());
    }

    /** @test **/
    public function presets_can_be_passed_for_relations_inline()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'mediumSized', 'departments')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(4, $company->departments->first()->employees->count());
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
    }

    /** @test **/
    public function regression_states_works_with_nested_relations()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'active', 'departments')
            ->with(2, 'departments.employees')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(2, $company->departments->first()->employees->count());
        $this->assertEquals(1, $company->departments->first()->active);
    }
}
