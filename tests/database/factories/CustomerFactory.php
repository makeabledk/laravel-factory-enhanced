<?php

namespace Makeable\LaravelFactory\Tests\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Makeable\LaravelFactory\Tests\Stubs\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition()
    {
        return [];
    }

    public function happy()
    {
        return ['satisfaction' => 5];
    }
}
