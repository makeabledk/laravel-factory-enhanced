<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Customer;
use Makeable\LaravelFactory\Tests\Stubs\Department;
use Makeable\LaravelFactory\Tests\Stubs\Image;
use Makeable\LaravelFactory\Tests\Stubs\User;
use Makeable\LaravelFactory\Tests\TestCase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_creates_models_with_no_relations()
    {
        $this->assertInstanceOf(User::class, User::factory()->create());
    }

    /** @test **/
    public function it_creates_models_without_prior_definitions()
    {
        $this->assertInstanceOf(Customer::class, Customer::factory()->create());
    }

    /** @test **/
    public function it_creates_models_even_when_no_factory_exists()
    {
        $this->assertInstanceOf(Image::class, Image::factory()->create([
            'imageable_type' => 'Foo',
            'imageable_id' => 1,
        ]));
    }

    /** @test **/
    public function it_creates_models_on_a_custom_connection()
    {
        $company = Company::factory()
            ->connection('secondary')
            ->create(['name' => 'Evil corp']);

        $this->assertNull(Company::query()->where('name', 'Evil corp')->first());
        $this->assertEquals($company->id, Company::on('secondary')->where('name', 'Evil corp')->first()->id);
    }

    /** @test **/
    public function it_makes_models_on_a_custom_connection()
    {
        $company = Company::factory()
            ->connection('secondary')
            ->make(['name' => 'Evil corp']);

        $this->assertEquals('secondary', $company->getConnectionName());
    }

    /** @test **/
    public function it_supports_giving_a_closure_as_count()
    {
        $companies = Company::factory()->count(new Sequence(1, 2));

        $this->assertEquals(1, $companies->make()->count());
        $this->assertEquals(2, $companies->make()->count());
    }

//    /** @test **/
//    public function it_applies_closures_when_a_condition_is_met()
//    {
//        $createTwice = function ($builder) {
//            $builder->times(2);
//        };
//
//        $this->assertInstanceOf(User::class, User::factory()->when(false, $createTwice)->create());
//        $this->assertInstanceOf(Collection::class, User::factory()->when(true, $createTwice)->create());
//    }
//
//    /** @test **/
//    public function it_applies_closures_given_certain_odds()
//    {
//        $createTwice = function ($builder) {
//            $builder->times(2);
//        };
//
//        // With decimal
//        $this->assertInstanceOf(User::class, User::factory()->odds(0 / 1, $createTwice)->create());
//        $this->assertInstanceOf(Collection::class, User::factory()->odds(1 / 1, $createTwice)->create());
//
//        // With 0-100
//        $this->assertInstanceOf(User::class, User::factory()->odds(0, $createTwice)->create());
//        $this->assertInstanceOf(Collection::class, User::factory()->odds(100, $createTwice)->create());
//
//        // With string percentage
//        $this->assertInstanceOf(User::class, User::factory()->odds('0%', $createTwice)->create());
//        $this->assertInstanceOf(Collection::class, User::factory()->odds('100%', $createTwice)->create());
//    }

    /** @test **/
    public function a_builder_can_be_tapped()
    {
        $createTwice = function ($builder) {
            $builder->count(2);
        };

        $this->assertInstanceOf(Collection::class, User::factory()->tap($createTwice)->create());
    }

    /** @test **/
    public function it_executes_defined_after_callbacks()
    {
        $factory = Department::factory()
            ->afterMaking(function ($department) {
                $department->forceFill(['active' => 1]);
            })
            ->afterCreating(function ($department) {
                $department->forceFill(['flagship' => 1]);
            });

        $this->assertEquals(1, ($made = $factory->make())->active);
        $this->assertEquals(0, $made->flagship);

        $this->assertEquals(1, ($created = $factory->create())->active);
        $this->assertEquals(1, $created->flagship);
    }

//    /** @test **/
//    public function regression_it_doesnt_throw_missing_state_exception_when_has_after_callback()
//    {
//        )->factory((Department::class, 'undefined-state', function ($department) {
//            $department->forceFill(['name' => 'HQ']);
//        });
//
//        $this->assertEquals('HQ', Department::factory())->state('undefined-state')->make()->name);
//
//        unset(app(StateManager::class)->afterMaking[Company::class]);
//    }

//    /** @test **/
//    public function regression_it_passes_inline_attributes_to_definitions()
//    {
//        $factory = $this->factory();
//        $factory->defineAs(Customer::class, 'special', function (Generator $faker, array $attributes) {
//            $this->assertEquals('bar', $attributes['foo']);
//
//            return [];
//        });
//
//        $customer = $factory->of(Customer::class, 'special')->make(['foo' => 'bar']);
//
//        $this->assertEquals('bar', $customer->foo);
//
//        unset($factory[Customer::class]['special']);
//    }

    /** @test **/
    public function regression_it_ignores_callables_when_expanding_attributes()
    {
        $company = Company::factory()->create([
            'tags' => ['Storage', 'Data'],
        ]);

        $this->assertEquals(['Storage', 'Data'], $company->tags);
    }

    /** @test **/
    public function regression_it_expands_closures_in_definition_attributes()
    {
        $company = Company::factory()->withOwner()->create();

        $this->assertInstanceOf(User::class, $company->owner);
    }
}
