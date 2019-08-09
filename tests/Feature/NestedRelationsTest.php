<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\TestCase;

class NestedRelationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_creates_models_with_nested_relations()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with('departments.manager')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
    }

    /** @test **/
    public function nested_relations_can_be_built_by_closures()
    {
        $company = $this->factory(Company::class)
            ->with('departments', function (FactoryBuilder $departments) {
                $departments
                    ->fill(['name' => 'foo'])
                    ->times(2)
                    ->with('manager');
            })
            ->create();

        $this->assertEquals(2, $company->departments->count());
        $this->assertEquals('foo', $company->departments->first()->name);
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
        $this->assertNotEquals(
            $company->departments->get(0)->manager->id,
            $company->departments->get(1)->manager->id
        );
    }

    /** @test **/
    public function nested_relations_can_be_specified_separate_function_calls()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(1, 'departments')
            ->with(1, 'departments.manager')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
    }
}
