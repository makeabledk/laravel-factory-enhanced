<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait NormalizesAttributes
{
    /**
     * Ensure a query result is returned as a collection.
     *
     * @param  mixed  $result
     * @return \Illuminate\Support\Collection
     */
    protected function collect($result = null)
    {
        return $result instanceof Model
            ? $result->newCollection([$result])
            : $result;
//
//        if ($result instanceof Model) {
//            $result = [$result];
//        }
//
//        return new Collection($result);
    }

    /**
     * Ensure a query result is returned as a model.
     *
     * @param $results
     * @return Model
     */
    protected function collectModel($results)
    {
        return $this->collect($results)->first();
    }

    /**
     * Ensure a subject is a callable.
     *
     * @param $arg
     * @return callable
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
