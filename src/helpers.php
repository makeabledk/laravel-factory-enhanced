<?php

namespace Makeable\LaravelFactory;

use Facades\Makeable\LaravelFactory\ModelHistory as ModelHistory;

function factory($modelClass, ...$arguments): Factory
{
    return Factory::factoryForModel($modelClass)->apply(...$arguments);
}
//
//function current($modelClass): \Closure
//{
//    return fn () => latest($modelClass);
//}

function latest($modelClass): \Closure
{
    return function () use ($modelClass) {
        $result = ModelHistory::get($modelClass)->last();

        dump('Getting '.$modelClass.': '.$result->id);

        return $result;
    };
}
