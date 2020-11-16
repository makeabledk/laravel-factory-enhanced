<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Collection;

class ArgumentParser
{
    public static function apply(Collection $arguments, Factory $factory)
    {
        return $arguments->reduce(function (Factory $factory, $arg) {
            if (is_null($arg)) {
                return $factory;
            }

            if (is_numeric($arg)) {
                return $factory->count($arg);
            }

            if (is_array($arg) && ! isset($arg[0])) {
                return $factory->fill($arg);
            }

            if (is_callable($arg) && ! is_string($arg)) {
                return $factory->tap($arg);
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