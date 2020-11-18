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
                return tap($factory->pipe($arg), function ($result) {
                    if (! $result instanceof Factory) {
                        throw new \BadMethodCallException("Closures must return a Factory instance"); // Todo - change?
                    }
                });
            }

            if (method_exists($factory, $arg)) {
                return call_user_func([$factory, $arg]);
            }

            throw new \BadMethodCallException('Unexpected argument: '. $arg);

//
//            if (is_string($arg) && $this->isValidRelation($arg)) {
//                $this->path = $arg;
//
//                return;
//            }
//
//            if (is_string($arg) && $this->stateManager->definitionExists($this->getRelatedClass(), $arg)) {
//                $this->definition = $arg;
//
//                return;
//            }
//
//            if ($this->stateManager->presetsExists($this->getRelatedClass(), $arg)) {
//                $this->presets = array_merge($this->presets, Arr::wrap($arg));
//
//                return;
//            }
//
//            // If nothing else, we'll assume $arg represent some state.
//            return $this->states = array_merge($this->states, Arr::wrap($arg));
        }, $factory);
    }

    protected static function fill(Factory $factory, array $attributes)
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

//    /**
//     * Parse each individual argument given to 'with'.
//     *
//     * @param mixed $arg
//     * @return void
//     */
//    protected function parseArgument($arg)
//    {

//    }

}