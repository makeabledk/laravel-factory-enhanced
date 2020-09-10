<?php

use Faker\Generator;

$factory->define(\Makeable\LaravelFactory\Tests\Stubs\User::class, function (Generator $faker) {
    return [
        'name' => $faker->company,
        'email' => $faker->email,
        'password' => bcrypt('test')
    ];
});