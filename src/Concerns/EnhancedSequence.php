<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Database\Eloquent\Factories\Sequence;

trait EnhancedSequence
{
    /**
     * Support a "method name" string given as sequence value.
     * It will invoke the method on the Factory and grab
     * any attributes the method has applied.
     */
    public function sequence(...$values): self
    {
        $values = array_map(function ($value) {
            if (is_string($value) && method_exists($this, $value)) {
                return function () use ($value) {
                    return (new static)->$value()->count(null)->raw();
                };
            }

            return $value;
        }, $values);

        $sequence = new Sequence(...$values);

        return $this->state(function () use ($sequence) {
            return ($attributes = $sequence()) instanceof \Closure
                ? $attributes()
                : $attributes;
        });
    }
}
