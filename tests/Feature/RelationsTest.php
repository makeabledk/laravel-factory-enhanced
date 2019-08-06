<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Tests\Stubs\Company;
use Makeable\LaravelFactory\Tests\Stubs\Division;
use Makeable\LaravelFactory\Tests\Stubs\Image;
use Makeable\LaravelFactory\Tests\TestCase;

class RelationsTest extends TestCase
{
    use RefreshDatabase;

    // DIFFERENT RELATIONSHIP TYPES

    /** @test **/
    public function it_creates_models_with_belongs_to_relations()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals(1, $company->owner->id);
    }

    /** @test **/
    public function it_creates_models_with_has_many_relations()
    {
        $company = $this->factory(Company::class)
            ->with(2, 'divisions')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertInstanceOf(Division::class, $company->divisions->first());
        $this->assertEquals(2, $company->divisions->count());
    }

    /** @test **/
    public function it_creates_models_with_morph_many_relations()
    {
        $company = $this->factory(Company::class)
            ->with('logo')
            ->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertInstanceOf(Image::class, $company->logo);
    }

    /** @test **/
    public function it_creates_models_with_belongs_to_many_relations()
    {
        $division = $this->factory(Division::class)
            ->with(2, 'employees')
            ->create();

        $this->assertInstanceOf(User::class, $division->employees->first());
        $this->assertEquals(2, $division->employees->count());
    }

    /** @test **/
    public function it_creates_models_with_multiple_relations()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(2, 'divisions')
            ->create();

        $this->assertInstanceOf(User::class, $company->owner);
        $this->assertInstanceOf(Division::class, $company->divisions->first());
    }

    // FUNCTIONALITY AND BEHAVIOR

    /** @test **/
    public function the_same_relation_can_be_created_multiple_times_using_andWith()
    {
        $company = $this->factory(Company::class)
            ->with('owner')
            ->with(1, 'divisions')
            ->andWith(1, 'divisions.manager')
            ->create();

        $this->assertEquals(2, $company->divisions->count());
        $this->assertNull($company->divisions->first()->manager);
        $this->assertInstanceOf(User::class, $company->divisions->last()->manager);
    }

    /** @test **/
    public function additional_attributes_can_be_passed_inline_for_relations()
    {
        $company = $this->factory(Company::class)
            ->with(1, 'divisions', ['active' => 1])
            ->with('divisions.manager', ['password' => 'foobar'])
            ->create();

        $this->assertEquals(1, $company->divisions->first()->active);
        $this->assertEquals('foobar', $company->divisions->first()->manager->password);
    }

    /** @test **/
    public function it_throws_a_bad_method_call_on_missing_relations()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->factory(Company::class)->with(1, 'invalidRelation')->create();
    }

    /** @test **/
    public function it_accepts_pivot_attributes_on_belongs_to_many_relations()
    {
        $division = $this->factory(Division::class)->with(1, 'employees', function ($employee) {
            $employee->fillPivot(['started_at' => '2019-01-01 00:00:00']);
        })->create();

        $employees = $division->employees()->withPivot('started_at')->get();

        $this->assertEquals('2019-01-01 00:00:00', $employees->first()->pivot->started_at);
        $this->assertEquals(1, $employees->count());
    }

    /** @test **/
    public function it_accepts_closures_as_pivot_attributes_and_they_will_evaluate_on_each_model()
    {
        [$i, $dates] = [0, [now()->subMonth(), now()->subDay()]];

        $division = $this->factory(Division::class)->with(2, 'employees', function ($employee) use ($dates, &$i) {
            $employee->fillPivot(function ($faker) use ($dates, &$i) {
                return ['started_at' => $dates[$i++]];
            });
        })->create();

        $employees = $division->employees()->withPivot('started_at')->get();

        $this->assertEquals(2, $employees->count());
        $this->assertEquals($dates[0]->toDateTimeString(), $employees->get(0)->pivot->started_at);
        $this->assertEquals($dates[1]->toDateTimeString(), $employees->get(1)->pivot->started_at);
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
