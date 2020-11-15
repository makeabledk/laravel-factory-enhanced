<?php

namespace Makeable\LaravelFactory;

use Makeable\LaravelFactory\Concerns\BuildsRelationships;

class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    use BuildsRelationships;

    public static function factoryForModel($modelName)
    {
        $factory = method_exists($modelName, 'newFactory')
            ? $modelName::newFactory()
            : null;

        if ($factory === null) {
            $factory = static::resolveFactoryName($modelName);
        }

        if (is_string($factory) && class_exists($factory)) {
            return $factory::new();
        }

        return tap(self::new(), fn ($factory) => $factory->model = $modelName);
    }

    public function definition()
    {
        return [];
    }

    public function apply(...$args): self
    {

        return $this;
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
}