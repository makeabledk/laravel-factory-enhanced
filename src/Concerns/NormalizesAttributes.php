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
            return collect([$result]);
        }
        return $result;
    }

    /**
     * @param $results
     * @return Model
     */
    protected function collectModel($results)
    {
        if ($results instanceof Model) {
            return $results;
        }
        return collect($results)->first();
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