<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Division;
use Makeable\LaravelFactory\Tests\TestCase;

class SimpleRelationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    function it_creates_models_with_belongs_to_relations()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals(1, $company->owner->id);
    }

    /** @test **/
    function it_creates_models_with_has_many_relations()
    {
        $company = $this->factory(Company::class)
            ->with(2, 'divisions')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertInstanceOf(Division::class, $company->divisions->first());
        $this->assertEquals(2, $company->divisions->count());
    }

    /** @test **/
    function it_creates_models_with_multiple_relations()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(2, 'divisions')
            ->create();

        $this->assertInstanceOf(User::class, $company->owner);
        $this->assertInstanceOf(Division::class, $company->divisions->first());
    }

    /** @test **/
    function the_same_relation_can_be_created_multiple_times_using_andWith()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(1, 'divisions')
            ->andWith(1, 'divisions.manager')
            ->create();

        $this->assertEquals(2, $company->divisions->count());
        $this->assertNull($company->divisions->get(0)->manager);
        $this->assertInstanceOf(User::class, $company->divisions->get(1)->manager);
    }

    /** @test **/
    public function states_can_be_specified_for_nested_relations()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(2, 'happy', 'customers')
            ->with(3, 'active', 'divisions')
            ->with(3, 'divisions.employees')
            ->create();

        $this->assertEquals(3, $company->divisions->count());
        $this->assertEquals(2, $company->customers->count());
        $this->assertEquals(3, $company->divisions->first()->employees->count());
        $this->assertEquals(1, $company->divisions->first()->active);
        $this->assertEquals(5, $company->customers->first()->satisfaction);
    }

    /** @test **/
    public function additional_attributes_can_be_passed_inline()
    {
        $company = $this->factory(Company::class)
            ->with('owner', ['password' => 'foobar'])
            ->create();

        $this->assertEquals('foobar', $company->owner->password);
    }

    /** @test **/
    public function additional_attributes_can_be_passed_inline_for_nested_relations()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'divisions')
            ->with(2, 'divisions.manager', ['password' => 'foobar'])
            ->create();

        $this->assertEquals('foobar', $company->divisions->first()->manager->password);
    }

    /** @test **/
    public function it_throws_a_bad_method_call_on_missing_relations()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->factory(Company::class)->with(1, 'invalidRelation')->create();
    }

    /** @test **/
    public function regression_null_arguments_will_parse_as_state_and_then_ignored()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'divisions', null)
            ->create();

        $this->assertEquals(1, $company->divisions->count());
    }
}
