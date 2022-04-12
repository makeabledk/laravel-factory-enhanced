<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use function Makeable\LaravelFactory\count;
use Makeable\LaravelFactory\Factory;
use function Makeable\LaravelFactory\fill;
use function Makeable\LaravelFactory\inherit;
use function Makeable\LaravelFactory\sequence;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Department;
use Makeable\LaravelFactory\Tests\Stubs\Image;
use Makeable\LaravelFactory\Tests\Stubs\User;
use Makeable\LaravelFactory\Tests\TestCase;

class RelationsTest extends TestCase
{
    use RefreshDatabase;

    // DIFFERENT RELATIONSHIP TYPES

    /** @test **/
    public function it_creates_models_with_belongs_to_relations()
    {
        $company = Company::factory()
            ->with('owner')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals(1, $company->owner->id);
    }

    /** @test **/
    public function it_creates_models_with_has_many_relations()
    {
        $company = Company::factory()
            ->with(2, 'departments')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertInstanceOf(Department::class, $company->departments->first());
        $this->assertEquals(2, $company->departments->count());
    }

    /** @test **/
    public function it_creates_models_with_morph_many_relations()
    {
        $company = Company::factory()
            ->with('logo')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertInstanceOf(Image::class, $company->logo);
    }

    /** @test **/
    public function it_creates_models_with_belongs_to_many_relations()
    {
        $department = Department::factory()
            ->with(2, 'employees')
            ->create();

        $this->assertInstanceOf(User::class, $department->employees->first());
        $this->assertEquals(2, $department->employees->count());
    }

    /** @test **/
    public function it_creates_models_with_multiple_relations()
    {
        $company = Company::factory()
            ->with('owner')
            ->with(2, 'departments')
            ->create();

        $this->assertInstanceOf(User::class, $company->owner);
        $this->assertInstanceOf(Department::class, $company->departments->first());
    }

    // FUNCTIONALITY AND BEHAVIOR

    /** @test **/
    public function it_creates_related_models_on_the_same_connection()
    {
        $queries = [];

        DB::listen(function (QueryExecuted $e) use (&$queries) {
            $queries[$e->connectionName][] = $e->sql;
        });

        Company::factory()
            ->connection('secondary')
            ->for(User::factory(), 'owner')
            ->with('owner') // belongs-to
            ->with(1, 'departments') // has-many
            ->with(1, 'departments.employees') // belongs-to-many
            ->create();

        $this->assertNull(data_get($queries, 'primary'));
        $this->assertCount(5, data_get($queries, 'secondary'));

        $company = Company::on('secondary')->with('owner', 'departments.employees')->latest()->first();

        $this->assertInstanceOf(User::class, $company->owner);
        $this->assertEquals(1, $company->departments->count());
        $this->assertEquals(1, $company->departments->first()->employees->count());
    }

    /** @test **/
    public function the_same_relation_can_be_created_multiple_times_using_andWith()
    {
        $company = Company::factory()
            ->with('owner')
            ->with(1, 'departments')
            ->andWith(1, 'departments.manager')
            ->create();

        $this->assertEquals(2, $company->departments->count());
        $this->assertNull($company->departments->first()->manager);
        $this->assertInstanceOf(User::class, $company->departments->last()->manager);
    }

    /** @test **/
    public function additional_attributes_can_be_passed_inline_for_relations()
    {
        $company = Company::factory()
            ->with(1, 'departments', ['active' => 1])
            ->with('departments.manager', ['password' => 'foobar'])
            ->create();

        $this->assertEquals(1, $company->departments->first()->active);
        $this->assertEquals('foobar', $company->departments->first()->manager->password);
    }

    /** @test **/
    public function it_throws_a_bad_method_call_on_missing_relations()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessageMatches('/invalidRelation/');
        Company::factory()->with(1, 'invalidRelation')->create();
    }

    /** @test **/
    public function it_accepts_pivot_attributes_on_belongs_to_many_relations()
    {
        $department = Department::factory()->with(1, 'employees', function ($employee) {
            return $employee->fillPivot(['started_at' => '2019-01-01 00:00:00']);
        })->create();

        $employees = $department->employees()->withPivot('started_at')->get();

        $this->assertEquals('2019-01-01 00:00:00', $employees->first()->pivot->started_at);
        $this->assertEquals(1, $employees->count());
    }

    /** @test **/
    public function it_accepts_closures_as_pivot_attributes_and_they_will_evaluate_on_each_model()
    {
        [$i, $dates] = [0, [now()->subMonth(), now()->subDay()]];

        $department = Department::factory()
            ->with(2, 'employees', function ($employee) use ($dates, &$i) {
                return $employee->fillPivot(function (Department $department) use ($dates, &$i) {
                    return ['started_at' => $dates[$i++]];
                });
            })->create();

        $employees = $department->employees()->withPivot('started_at')->get();

        $this->assertEquals(2, $employees->count());
        $this->assertEquals($dates[0]->toDateTimeString(), $employees->get(0)->pivot->started_at);
        $this->assertEquals($dates[1]->toDateTimeString(), $employees->get(1)->pivot->started_at);
    }

    /** @test **/
    public function regression_null_arguments_will_parse_as_state_and_then_ignored()
    {
        $company = Company::factory()
            ->with(1, 'departments', null)
            ->create();

        $this->assertEquals(1, $company->departments->count());
    }

    /** @test **/
    public function regression_parent_model_is_available_as_second_argument()
    {
        // Laravel syntax
        $company = Company::factory()
            ->has(
                Department::factory()
                    ->count(2)
                    ->state(function (array $attributes, Company $company) {
                        return ['name' => $company->name.': Department'];
                    })
            )
            ->create();

        $this->assertStringContainsString($company->name, $company->departments->first()->name);

        // Enhanced syntax
        $company = Company::factory()
            ->with(2, 'departments', function ($builder) {
                $builder->fill(fn ($department, $company) => ['name' => $company->name.': Department']);
            })
            ->create();

        $this->assertStringContainsString($company->name, $company->departments->first()->name);
    }

    // NESTED RELATIONS

    /** @test **/
    public function it_creates_models_with_nested_relations()
    {
        $company = Company::factory()
            ->with('owner')
            ->with('departments.manager')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
    }

    /** @test **/
    public function nested_relations_can_be_built_by_closures()
    {
        $company = Company::factory()
            ->with('departments', function (Factory $departments) {
                return $departments
                    ->fill(['name' => 'foo'])
                    ->count(2)
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
        $company = Company::factory()
            ->with('owner')
            ->with(1, 'departments')
            ->with(1, 'departments.manager')
            ->create();

        $this->assertEquals(1, $company->departments->count());
        $this->assertInstanceOf(User::class, $company->departments->first()->manager);
    }

    // HELPER FUNCTIONS

    /** @test **/
    public function count_helper_may_be_used_for_dynamic_expressions()
    {
        $companies = Company::factory()
            ->count(2)
            ->with(count(new Sequence(1, 2)), 'departments')
            ->create();

        $this->assertEquals(1, $companies->first()->departments->count());
        $this->assertEquals(2, $companies->last()->departments->count());
    }

    /** @test **/
    public function fill_helper_may_be_used_to_access_parent()
    {
        $company = Company::factory()
            ->with(1, 'departments', fill(fn ($attributes, Company $parent) => ['name' => $parent->name]))
            ->create();

        $this->assertEquals($company->name, $company->departments->first()->name);
    }

    /** @test **/
    public function inherit_helper_may_be_used_to_fill_attributes_from_parent()
    {
        $company = Company::factory()
            ->with(1, 'departments', inherit('name'))
            ->create();

        $this->assertEquals($company->name, $company->departments->first()->name);
    }

    /** @test **/
    public function sequence_helper_may_be_used_to_apply_different_states()
    {
        $company = Company::factory()
            ->with(2, 'customers', sequence('happy', 'unhappy'))
            ->create();

        $this->assertEquals(5, $company->customers->first()->satisfaction);
        $this->assertEquals(1, $company->customers->last()->satisfaction);
    }

    /** @test **/
    public function regression_it_doesnt_create_belongs_to_relations_when_given_a_count_of_zero()
    {
        $company = Company::factory()
            ->with(0, 'owner')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertNull($company->owner);
    }
}
