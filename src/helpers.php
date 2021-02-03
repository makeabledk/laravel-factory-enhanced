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

    use Illuminate\Database\Eloquent\Model;

    function count($count)
    {
        return fn (Factory $factory) => $factory->count($count);
    }

    function fill($attributes)
    {
        return fn (Factory $factory) => $factory->fill($attributes);
    }

    function inherit(...$attributes)
    {
        return fn (Factory $factory) => $factory->fill(function ($attrs, Model $parent) use ($attributes) {
            return $parent->only($attributes);
        });
    }

    function sequence(...$sequence)
    {
        return fn (Factory $factory) => $factory->sequence(...$sequence);
    }
}
