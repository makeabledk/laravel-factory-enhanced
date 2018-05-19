<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Database\Eloquent\Model;

trait CollectsModels
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
}