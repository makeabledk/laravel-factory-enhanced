<?php

namespace Makeable\LaravelFactory\Concerns;

use Facades\Makeable\LaravelFactory\ModelHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait TracksCreatedModels
{
    protected function callAfterCreating(Collection $instances, ?Model $parent = null)
    {
        ModelHistory::track($instances);

        parent::callAfterCreating(...func_get_args());
    }
}