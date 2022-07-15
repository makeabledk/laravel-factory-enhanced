<?php

namespace Makeable\LaravelFactory\Tests\Database\Factories;

use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\Tests\Stubs\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function happy()
    {
        return $this->state([
            'satisfaction' => 5,
        ]);
    }

    public function unhappy()
    {
        return $this->state([
            'satisfaction' => 1,
        ]);
    }
}
