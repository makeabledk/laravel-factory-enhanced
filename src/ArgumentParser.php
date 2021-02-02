<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArgumentParser
{
    public static function apply(Collection $arguments, Factory $factory): Factory
    {
        return $arguments->reduce(function (Factory $factory, $arg) {
            if (is_null($arg)) {
                return $factory;
            }

            if (is_numeric($arg)) {
                return $factory->count($arg);
            }

            if (is_array($arg) && ! isset($arg[0])) {
                return static::fill($factory, $arg);
            }

            if (is_callable($arg) && ! is_string($arg)) {
                return $factory->tap($arg);
            }

            if (is_string($arg) || (is_array($arg) && is_string($arg[0]))) {
                return collect($arg)->reduce(fn ($factory, $method) => call_user_func([$factory, $method]), $factory);
            }

            throw new \InvalidArgumentException('Unexpected argument: '.$arg);
        }, $factory);
    }

    protected static function fill(Factory $factory, array $attributes): Factory
    {
        $pivotAttributes = [];

        foreach ($attributes as $attribute => $value) {
            if (Str::startsWith($attribute, 'pivot.')) {
                $pivotAttributes[Str::after($attribute, 'pivot.')] = $value;

                Arr::forget($attributes, $attribute);
            }
        }

        return $factory
            ->fill($attributes)
            ->fillPivot($pivotAttributes);
    }
}
