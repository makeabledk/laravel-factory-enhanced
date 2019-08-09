<?php

namespace Makeable\LaravelFactory;

use Closure;
use InvalidArgumentException;
use Makeable\LaravelFactory\Concerns\NormalizesAttributes;

class StateManager
{
    use NormalizesAttributes;

    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The registered model presets.
     *
     * @var array
     */
    protected $presets = [];

    /**
     * The registered model states.
     *
     * @var array
     */
    protected $states = [];

    /**
     * The registered after making callbacks.
     *
     * @var array
     */
    public $afterMaking = [];

    /**
     * The registered after creating callbacks.
     *
     * @var array
     */
    public $afterCreating = [];

    /**
     * Define a class with a given short-name.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable|array $builder
     * @return $this
     */
    public function define($class, $name, $builder)
    {
        $this->definitions[$class][$name] = $this->wrapCallable($builder);

        return $this;
    }

    /**
     * @param $class
     * @return bool
     */
    public function definitionExists($class)
    {
        return isset($this->definitions[$class]);
    }

    /**
     * @param $class
     * @return $this
     */
    public function forgetDefinitions($class)
    {
        unset($this->definitions[$class]);

        return $this;
    }

    /**
     * @param $class
     * @param $name
     * @return Closure
     */
    public function getDefinition($class, $name)
    {
        return data_get($this->definitions, "{$class}.{$name}") ?: $this->wrapCallable([]);
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param  string  $class
     * @param  string  $preset
     * @param  callable|array  $builder
     * @return $this
     */
    public function preset($class, $preset, $builder)
    {
        $this->presets[$class][$preset] = $this->wrapCallable($builder);

        return $this;
    }

    /**
     * @param $class
     * @param $preset
     * @return Closure
     */
    public function getPreset($class, $preset)
    {
        $builder = data_get($this->presets, "{$class}.{$preset}");

        if (! $builder) {
            throw new InvalidArgumentException("Unable to locate [{$preset}] preset for [{$class}].");
        }

        return $builder;
    }

    /**
     * Define a state with a given set of attributes.
     *
     * @param  string  $class
     * @param  string  $state
     * @param  callable|array  $builder
     * @return $this
     */
    public function state($class, $state, $builder)
    {
        $this->states[$class][$state] = $this->wrapCallable($builder);

        return $this;
    }

    /**
     * @param $class
     * @param $state
     * @return Closure
     */
    public function getState($class, $state)
    {
        $builder = data_get($this->states, "{$class}.{$state}");

        if (! $builder) {
            throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$class}].");
        }

        return $builder;
    }

    /**
     * @param $class
     * @param $name
     * @param callable $callback
     * @return $this
     */
    public function afterMaking($class, $name, callable $callback)
    {
        $this->afterMaking[$class][$name][] = $callback;

        return $this;
    }

    /**
     * @param $class
     * @param $name
     * @param callable $callback
     * @return $this
     */
    public function afterCreating($class, $name, callable $callback)
    {
        $this->afterCreating[$class][$name][] = $callback;

        return $this;
    }
}
