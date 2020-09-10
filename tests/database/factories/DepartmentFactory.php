<?php

use Faker\Generator;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\Tests\Stubs\Department;

$factory->define(Department::class, function (Generator $faker) {
    return ['name' => $faker->company];
});

$factory->state(Department::class, 'active', ['active' => 1]);

$factory->state(Department::class, 'flagship', function (Generator $faker) {
    return ['flagship' => 1];
});

$factory->preset(Department::class, 'mediumSized', function (FactoryBuilder $department, Generator $faker) {
    $department->with(1, 'manager')->with(4, 'employees');
});