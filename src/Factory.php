<?php

namespace Makeable\LaravelFactory;

class Factory extends \Illuminate\Database\Eloquent\Factory
{

//    protected $stateProviders = [];

    /**
     * Create a builder for the given model.
     *
     * @param  string  $class
     * @param  string  $name
     * @return FactoryBuilder
     */
    public function of($class, $name = 'default')
    {
//        return new Factory($class, $name);

        return new FactoryBuilder($class, $name, $this->definitions, $this->states, $this->faker);
    }
//
//    /**
//     * Define a state with a given set of attributes.
//     *
//     * @param  string  $class
//     * @param  string  $state
//     * @param  callable|array  $attributes
//     * @return $this
//     */
//    public function stateProvider($provider)
//    {
//        $this->stateProviders[] = $provider;
//
//        return $this;
//    }
}