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
    protected $afterMaking = [];

    /**
     * The registered after creating callbacks.
     *
     * @var array
     */
    protected $afterCreating = [];

    /**
     * @var array
     */
    protected $stateProviders = [];

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
     * @param $states
     * @return array
     */
    public function getStates($class, $states)
    {
        return array_map(function ($state) use ($class) {
            return $this->getState($class, $state);
        }, $states);
    }

    /**
     * @param $class
     * @param $state
     * @return mixed
     */
    public function getState($class, $state)
    {
        $builder = data_get($this->states, "{$class}.{$state}") ?: $this->wrapCallable([]);

//        if (! $builder) {
//            throw new InvalidArgumentException("Unable to locate [{$state}] state for [{$class}].");
//        }

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
     * @param $state
     * @return array
     */
    public function getAfterMakingCallbacks($class, $state)
    {
        return data_get($this->afterMaking, "{$class}.{$state}", []);
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

    /**
     * @param $class
     * @param $state
     * @return array
     */
    public function getAfterCreatingCallbacks($class, $state)
    {
        return data_get($this->afterCreating, "{$class}.{$state}", []);
    }

    /**
     * @param $provider
     * @return $this
     */
    public function provider($provider)
    {
        $this->stateProviders[] = $provider;

        return $this;
    }
//
//    /**
//     * @param $builder
//     * @return Closure
//     */
//    protected function makeCallable($builder)
//    {
//        if (! is_callable($builder)) {
//            $builder = function () use ($builder) {
//                return $builder;
//            };
//        }
//        return $builder;
//    }

//
//    /**
//     * @param $class
//     * @param $state
//     * @return Closure | null
//     */
//    public function get($class, $state)
//    {
//        foreach ($this->providers as $provider) {
//            if ($fn = $provider->get($class, $state)) {
//                return $fn;
//            }
//        }
//        return null;
//    }
}