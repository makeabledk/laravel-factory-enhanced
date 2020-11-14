<?php

namespace Makeable\LaravelFactory\Tests\Stubs;

trait HasFactory
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        $name = "\\Makeable\\LaravelFactory\\Tests\\Database\\Factories\\".class_basename(static::class)."Factory";

        return new $name;
    }
}