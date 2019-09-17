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
            ->with('divisions.manager')
            ->create();

        $this->assertEquals(1, $company->divisions->count());
        $this->assertInstanceOf(User::class, $company->divisions->first()->manager);
    }

    /** @test **/
    public function nested_relations_can_be_composed_by_array_syntax()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with([
                'divisions' => 2,
                'divisions.manager',
            ])
            ->create();

        $this->assertEquals(2, $company->divisions->count());
        $this->assertInstanceOf(User::class, $company->divisions->first()->manager);
        $this->assertNotEquals(
            $company->divisions->get(0)->manager->id,
            $company->divisions->get(1)->manager->id
        );
    }

    /** @test **/
    public function nested_relations_can_be_customized_by_closures()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with([
                'divisions' => function (FactoryBuilder $divisions) {
                    $divisions
                        ->fill(['name' => 'foo'])
                        ->times(2)
                        ->with('manager');
                },
            ])
            ->create();

        $this->assertEquals(2, $company->divisions->count());
        $this->assertEquals('foo', $company->divisions->first()->name);
        $this->assertInstanceOf(User::class, $company->divisions->first()->manager);
        $this->assertNotEquals(
            $company->divisions->get(0)->manager->id,
            $company->divisions->get(1)->manager->id
        );
    }

    /** @test **/
    public function nested_relations_can_be_specified_separate_function_calls()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(1, 'divisions')
            ->with(1, 'divisions.manager')
            ->create();

        $this->assertEquals(1, $company->divisions->count());
        $this->assertInstanceOf(User::class, $company->divisions->first()->manager);
    }
}
