<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelFactory\Factory;

trait EnhancedCount
{
    /**
     * Overwrite core method to allow closures
     *
     * @param  int|callable|null  $count
     * @return $this
     */
    public function count($count): self
    {
        return $this->newInstance(['count' => $count]);
    }

    public function make($attributes = [], ?Model $parent = null)
    {
        return $this->withCalculatedCount(fn () => parent::make(...func_get_args()));
    }

    public function raw($attributes = [], ?Model $parent = null)
    {
        return $this->withCalculatedCount(fn () => parent::raw(...func_get_args()));
    }

    protected function withCalculatedCount(\Closure $callback)
    {
        $backup = $this->count;


        if (is_callable($this->count)) {
            $this->count = call_user_func($this->count);
        }

        return tap($callback(), fn () => $this->count = $backup);
    }
}