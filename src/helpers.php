<?php

use Makeable\LaravelFactory\Factory;

if (! function_exists('factory')) {
    function factory($model, ...$arguments)
    {
        return Factory::factoryForModel($model)->apply(...$arguments);
    }
}
