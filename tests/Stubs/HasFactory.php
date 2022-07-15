<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

trait HasFactory
{
    use \Makeable\LaravelFactory\HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        $name = '\\Makeable\\LaravelFactory\\Tests\\Database\\Factories\\'.class_basename(static::class).'Factory';

        if (class_exists($name)) {
            return new $name;
        }
    }
}
