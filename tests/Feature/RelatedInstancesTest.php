<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Customer;
use Makeable\LaravelFactory\Tests\Stubs\Division;
use Makeable\LaravelFactory\Tests\TestCase;

class RelatedInstancesTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    function it_will_associate_a_belongs_to_model_instance_instead_of_creating_through_factory()
    {
        $user = $this->factory(User::class)->create();
        $company = $this->factory(Company::class)
            ->with('owner', $user)
            ->create();

        $this->assertEquals(1, User::count());
        $this->assertEquals($user->id, $company->owner->id);
    }

    /** @test **/
    function it_will_associate_only_the_first_item_in_a_belongs_to_collection()
    {
        $users = $this->factory(User::class)->times(2)->create();
        $company = $this->factory(Company::class)
            ->with('owner', $users)
            ->create();

        $this->assertEquals(2, User::count());
        $this->assertEquals($users->first()->id, $company->owner->id);
    }

    /** @test **/
    function it_accepts_a_collection_of_instances_to_use_for_belongs_to_many_relations()
    {
        $users = $this->factory(User::class)->times(2)->create();
        $division = $this->factory(Division::class)
            ->with('employees', $users)
            ->create();

        $this->assertEquals([1,2], $division->employees->pluck('id')->toArray());
    }

    /** @test **/
    function it_accepts_a_collection_of_instances_and_tops_up_with_factory_to_specified_amount()
    {
        // Top up with 1 factory employee
        $division1 = $this->factory(Division::class)
            ->with(3, 'employees', $this->factory(User::class)->times(2)->create())
            ->create();

        $this->assertEquals([1,2,3], $division1->employees->pluck('id')->toArray());

        // Slice given users to 2 employees
        $division2 = $this->factory(Division::class)
            ->with(2, 'employees', $this->factory(User::class)->times(3)->create())
            ->create();

        $this->assertEquals([4,5], $division2->employees->pluck('id')->toArray());
    }

    /** @test **/
    public function test_it_creates_nested_relations_when_using_instances()
    {
        $company = $this->factory(Company::class)->create();

        $customer = $this->factory(Customer::class)
            ->with('company', $company)
            ->with(2, 'company.divisions')
            ->create();

        $this->assertEquals(2, $customer->company->divisions->count());
    }
}