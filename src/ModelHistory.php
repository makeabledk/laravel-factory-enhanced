<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Collection;

class ModelHistory
{
    protected ?Collection $history;

    public function get($modelClass): Collection
    {
        return $this->history()->get($modelClass) ?? collect();
    }

    public function track($models)
    {
        $models = $models instanceof Collection ? $models : collect([$models]);

        if ($models->count()) {
            $class = get_class($models->first());

            dump('Track '.$class.': '.$models->first()->id);

            $this->history()->put($class, $this->get($class)->concat($models));
        }
    }

    protected function history(): Collection
    {
        return $this->history ??= collect();
    }
}
