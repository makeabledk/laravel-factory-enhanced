<?php

namespace Makeable\LaravelFactory;

use Makeable\LaravelFactory\Concerns\EnhancedCount;
use Makeable\LaravelFactory\Concerns\EnhancedRelationships;
use Makeable\LaravelFactory\Concerns\EnhancedSequence;

class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    use EnhancedCount,
        EnhancedRelationships,
        EnhancedSequence;

    protected bool $mutating = false;

    public static function factoryForModel($modelName): self
    {
        if (method_exists($modelName, 'newFactory') && ($factory = $modelName::newFactory())) {
            return $factory;
        }

        $factory = static::resolveFactoryName($modelName);

        if (is_string($factory) && class_exists($factory)) {
            return $factory::new();
        }

        return Factory::new()->tap(fn ($factory) => $factory->model = $modelName);
    }

    public function apply(...$args): self
    {
        return ArgumentParser::apply(collect($args), $this);
    }

    public function definition()
    {
        return [];
    }

    public function fill($attributes): self
    {
        return $this->state($attributes);
    }

    public function fillPivot($attributes): self
    {
        return $this->newInstance()->tap(fn (self $factory) => array_push($factory->pivot, $attributes));
    }

    public function pipe(callable $callback): self
    {
        return call_user_func($callback, $this);
    }

    public function tap($callback = null): self
    {
        $this->mutating = true;

        call_user_func($callback, $this);

        $this->mutating = false;

        return $this;
    }

    protected function newInstance(array $arguments = [])
    {
        if ($this->mutating) {
            foreach ($arguments as $argument => $value) {
                $this->$argument = $value;
            }

            return $this;
        }

        return parent::newInstance($arguments)->tap(function (self $factory) {
            $factory->relations = $this->relations;
            $factory->pivot = $this->pivot;
            $factory->model = $this->model;
        });
    }
}
