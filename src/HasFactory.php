<?php

namespace Makeable\LaravelFactory;

use Illuminate\Database\Eloquent\Factories\Factory;

trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  mixed  $parameters
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public static function factory(...$parameters)
    {
        return factory(static::class, ...$parameters);
//        $factory = static::newFactory() ?: Factory::factoryForModel(get_called_class());
//
//        return $factory
//            ->count(is_numeric($parameters[0] ?? null) ? $parameters[0] : null)
//            ->state(is_array($parameters[0] ?? null) ? $parameters[0] : ($parameters[1] ?? []));
    }

//    public static function factory(...$parameters)
//    {
//
//    }
}
