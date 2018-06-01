<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Database\Eloquent\Model;

trait NormalizesAttributes
{
    /**
     * @param $result
     * @return \Illuminate\Support\Collection
     */
    protected function collect($result)
    {
        if ($result instanceof Model) {
            $result = [$result];
        }
        return collect($result);
    }

    /**
     * @param $results
     * @return Model
     */
    protected function collectModel($results)
    {
        return $this->collect($results)->first();
    }

    /**
     * @param $arg
     * @return Callable
     */
    protected function wrapCallable($arg)
    {
        if (! is_callable($arg) || is_string($arg)) {
            return function () use ($arg) {
                return $arg;
            };
        }
        return $arg;
    }
}