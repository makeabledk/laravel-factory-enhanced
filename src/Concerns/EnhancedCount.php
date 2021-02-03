<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Database\Eloquent\Model;

trait EnhancedCount
{
    /**
     * Overwrite core method to allow closures.
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
        return $this->withCalculatedCount(fn () => parent::make($attributes, $parent));
    }

    public function raw($attributes = [], ?Model $parent = null)
    {
        return $this->withCalculatedCount(fn () => parent::raw($attributes, $parent));
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
