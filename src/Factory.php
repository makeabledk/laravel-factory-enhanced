<?php

namespace Makeable\LaravelFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Tappable;
use Makeable\LaravelFactory\Concerns\BuildsRelationships;

class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    use BuildsRelationships,
        Tappable;

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

    public function definition()
    {
        return [];
    }

    public function apply(...$args): self
    {
        return ArgumentParser::apply(collect($args), $this);
    }

    public function fill($attributes): self
    {
        return $this->state($attributes);
    }

    public function fillPivot($attributes): self
    {
        return $this->newInstance()->tap(fn (self $factory) => array_push($factory->pivot, $attributes));
    }

    /**
     * Build the model with specified relations.
     *
     * @param mixed ...$args
     * @return static
     */
    public function with(...$args): self
    {
        return $this->loadRelation(
            new RelationRequest($this->model, $this->currentBatch, $args)
        );
    }

    /**
     * Build relations in a new batch. Multiple batches can be
     * created on the same relation, so that ie. multiple
     * has-many relations can be configured differently.
     *
     * @param mixed ...$args
     * @return static
     */
    public function andWith(...$args): self
    {
        return $this->newBatch()->with(...$args);
    }

    public function pipe(callable $callback)
    {
        return call_user_func($callback, $this);
    }

    protected function createChildren(Model $model)
    {
        $this->withRelationsApplied(fn () => parent::createChildren($model));
    }

    protected function getRawAttributes(?Model $parent)
    {
        return $this->withRelationsApplied(fn () => parent::getRawAttributes($parent));
    }

    protected function newInstance(array $arguments = [])
    {
        return parent::newInstance($arguments)->tap(function (self $factory) {
            $factory->relations = $this->relations;
            $factory->pivot = $this->pivot;
            $factory->model = $this->model;
        });
    }
}
