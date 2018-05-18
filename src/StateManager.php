<?php

namespace Makeable\LaravelFactory;

use Closure;

class StateManager
{
    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @param $provider
     * @return $this
     */
    public function add($provider)
    {
        $this->providers[] = $provider;

        return $this;
    }

    /**
     * @param $class
     * @param $state
     * @return Closure | null
     */
    public function get($class, $state)
    {
        foreach ($this->providers as $provider) {
            if ($fn = $provider->get($class, $state)) {
                return $fn;
            }
        }
        return null;
    }
}