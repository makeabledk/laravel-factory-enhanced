<?php

use Makeable\LaravelFactory\Factory;

if (! function_exists('factory')) {
    function factory($model, ...$arguments)
    {
        return Factory::factoryForModel($model)->apply(...$arguments);
//
//        $factory = app(Factory::class);
//
//        if (isset($amount) && is_int($amount)) {
//            return $factory->of($class)->times($amount);
//        }
//
//        return $factory->of($class);
    }
}
