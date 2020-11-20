<?php

namespace Makeable\LaravelFactory\Tests\Database\Factories;

use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\Tests\Stubs\Department;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
        ];
    }

    public function active()
    {
        return $this->state([
            'active' => 1,
        ]);
    }

    public function flagship()
    {
        return $this->state([
            'flagship' => 1,
        ]);
    }

    public function mediumSized()
    {
        return $this
            ->with(1, 'manager')
            ->with(4, 'employees');
    }
}
