<?php

namespace Makeable\LaravelFactory;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  mixed  $parameters
     * @return \Makeable\LaravelFactory\Factory
     */
    public static function factory(...$parameters)
    {
        return Factory::factoryForModel(static::class)->apply(...$parameters);
    }
}
