<?php

namespace Makeable\LaravelFactory;

use Closure;
use Illuminate\Support\Arr;
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
     * Check if a definition exists.
     *
     * @param $class
     * @param null $name
     * @return bool
     */
    public function definitionExists($class, $name = null)
    {
        return is_null($name)
            ? isset($this->definitions[$class])
            : isset($this->definitions[$class][$name]);
    }

    /**
     * Delete an existing definition.
     *
     * @param $class
     * @return $this
     */
    public function forgetDefinitions($class)
    {
        unset($this->definitions[$class]);

        return $this;
    }

    /**
     * Get a definition.
     *
     * @param $class
     * @param $name
     * @return Closure
     */
    public function getDefinition($class, $name)
    {
        return data_get($this->definitions, "{$class}.{$name}") ?: $this->wrapCallable([]);
    }

    /**
     * Define a preset that may later be used to configure a factory.
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
     * Check if presets exists.
     *
     * @param $class
     * @param $presets
     * @return bool
     */
    public function presetsExists($class, $presets)
    {
        return collect($presets)->reject(function ($preset) use ($class) {
            return data_get($this->presets, "{$class}.{$preset}") !== null;
        })->isEmpty();
    }

    /**
     * Get a preset.
     *
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
     * Check if states exists.
     *
     * @param $class
     * @param $states
     * @return bool
     */
    public function statesExists($class, $states)
    {
        return collect($states)->reject(function ($states) use ($class) {
            return data_get($this->states, "{$class}.{$states}") !== null;
        })->isEmpty();
    }

    /**
     * Get a state.
     *
     * @param $class
     * @param $state
     * @return Closure
     */
    public function getState($class, $state)
    {
        $builder = data_get($this->states, "{$class}.{$state}");

        // TODO fix inconsistency with Laravel - need to check if after-callback exists
        if (! $builder) {
            throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$class}].");
        }

        return $builder;
    }

    /**
     * Define a callback to run after making a model.
     *
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
     * Define a callback to run after creating a model.
     *
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
