<?php

use Makeable\LaravelFactory\Factory;

if (! function_exists('factory')) {
    function factory($class, $amount = null)
    {
        $factory = app(Factory::class);

        if (isset($amount) && is_int($amount)) {
            return $factory->of($class)->times($amount);
        }

        return $factory->of($class);
    }
}
