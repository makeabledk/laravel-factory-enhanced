<?php

namespace Makeable\LaravelFactory\Tests\Database\Factories;

use App\Models\User;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\Tests\Stubs\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
        ];
    }

    public function startup()
    {
        return $this
            ->with(1, 'departments')
            ->with(1, 'departments.employees');
    }

    public function withOwner()
    {
        return $this->state([
            'owner_id' => function () {
                return User::factory()->create()->id;
            },
        ]);
    }
}
