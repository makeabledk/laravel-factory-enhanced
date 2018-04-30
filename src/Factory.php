<?php

namespace Makeable\LaravelFactory;

class Factory extends \Illuminate\Database\Eloquent\Factory
{
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
//    public function definitionExists($definition)
//    {
//
//    }
//
//    public function stateExists($state)
//    {
//
//    }
}