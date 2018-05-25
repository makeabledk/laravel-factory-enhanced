<?php

namespace Makeable\LaravelFactory;

use Closure;

class DefaultStateProvider
{
    /**
     * The registered model states.
     *
     * @var array
     */
    protected $states = [];

    /**
     * @param $class
     * @param $state
     * @param $attributes
     * @return $this
     */
    public function add($class, $state, $attributes)
    {
        $this->states[$class][$state] = $attributes;

        return $this;
    }

    /**
     * @param $class
     * @param $state
     * @return Closure | null
     */
    public function get($class, $state)
    {
        return data_get($this->states, "{$class}.{$state}");
    }

    /**
     * @param $attributes
     * @return Closure
     */
    protected function wrapClosure($attributes)
    {
        if (! $attributes instanceof Closure) {
            $attributes = function () use ($attributes) {
                return $attributes;
            };
        }
        return $attributes;
    }
}
