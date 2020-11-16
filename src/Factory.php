<?php

namespace Makeable\LaravelFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Tappable;
use Makeable\LaravelFactory\Concerns\BuildsRelationships;

class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    use BuildsRelationships;

    public static function factoryForModel($modelName)
    {
        if (method_exists($modelName, 'newFactory') && ($factory = $modelName::newFactory())) {
            return $factory;
        }

        $factory = static::resolveFactoryName($modelName);

        if (is_string($factory) && class_exists($factory)) {
            return $factory::new();
        }

        return tap(Factory::new(), fn ($factory) => $factory->model = $modelName);
    }

    public function definition()
    {
        return [];
    }

    public function apply(...$args): self
    {
        return ArgumentParser::apply(collect($args), $this);

//        return $this;
    }

    public function fill($attributes)
    {
        return $this->state($attributes);
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

    public function tap(callable $callback): self
    {
        $result = call_user_func($callback);

        return $result instanceof self ? $result : $this;
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
        return tap(parent::newInstance($arguments), fn (self $factory) => $factory->relations = $this->relations);
    }
}