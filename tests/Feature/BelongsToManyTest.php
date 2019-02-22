<?php

namespace Makeable\LaravelFactory\Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\LaravelFactory\Tests\Stubs\Division;
use Makeable\LaravelFactory\Tests\TestCase;

class BelongsToManyTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    function it_creates_models_with_belongs_to_many_relations()
    {
        $division = $this->factory(Division::class)
            ->with(2, 'employees')
            ->create();

        $this->assertInstanceOf(User::class, $division->employees->first());
        $this->assertEquals(2, $division->employees->count());
    }

    /** @test **/
    function it_accepts_pivot_attributes_on_belongs_to_many_relations()
    {
        $division = $this->factory(Division::class)->with(1, 'employees', function ($employee) {
            $employee->fillPivot(['started_at' => '2019-01-01 00:00:00']);
        })->create();

        $employees = $division->employees()->withPivot('started_at')->get();

        $this->assertEquals('2019-01-01 00:00:00', $employees->first()->pivot->started_at);
        $this->assertEquals(1, $employees->count());
    }

    /** @test **/
    function it_accepts_closures_as_pivot_attributes_and_they_will_evaluate_on_each_model()
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
}