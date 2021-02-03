<?php

namespace {
    if (! function_exists('factory')) {
        function factory($model, ...$arguments)
        {
            return \Makeable\LaravelFactory\Factory::factoryForModel($model)->apply(...$arguments);
        }
    }
}

namespace Makeable\LaravelFactory {
    function count($count)
    {
        return fn (Factory $factory) => $factory->count($count);
    }

    function fill($attributes)
    {
        return fn (Factory $factory) => $factory->fill($attributes);
    }

    function sequence($sequence)
    {
        return fn (Factory $factory) => $factory->sequence($sequence);
    }
}
