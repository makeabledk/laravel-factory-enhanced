<?php

namespace Makeable\LaravelFactory\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\RelationRequest;

trait EnhancedRelationships
{
    protected int $currentBatch = 0;

    protected array $relations = [];

    protected array $pivot = [];

    /**
     * Build the model with specified relations.
     *
     * @param  mixed  ...$args
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
     * @param  mixed  ...$args
     * @return static
     */
    public function andWith(...$args): self
    {
        return $this->newBatch()->with(...$args);
    }

    /**
     * Load a RelationRequest onto current FactoryBuilder.
     *
     * @param  \Makeable\LaravelFactory\RelationRequest  $request
     * @return \Makeable\LaravelFactory\Concerns\EnhancedRelationships
     */
    public function loadRelation(RelationRequest $request): self
    {
        $related = $this->pushRelatedFactory($request);

        // Recursively create factories until no further nesting.
        if ($request->hasNesting()) {
            $this->pushRelatedFactory($request, $related->loadRelation($request->createNestedRequest()));

            return $this;
        }

        // Apply the request onto the final relationship factory.
        $this->pushRelatedFactory($request, $related->apply(...$request->arguments));

        return $this;
    }

    protected function pushRelatedFactory(RelationRequest $request, self $factory = null): Factory
    {
        $path = implode('.', [
            $request->loadMethod(),
            $request->getRelationName(),
            $request->batch,
        ]);

        $factory = $factory
            ?? data_get($this->relations, $path)
            ?? static::factoryForModel($request->getRelatedClass());

        return tap($factory, fn () => data_set($this->relations, $path, $factory));
    }

    protected function withRelationsApplied(Closure $callback)
    {
        $self = $this;

        foreach ($this->relations as $method => $relations) {
            foreach ($relations as $relationship => $factories) {
                foreach ($factories as $batch => $factory) {
                    if ($factory->count === 0) {
                        continue;
                    }

                    if ($method === 'for') {
                        $factory = $factory->count(null);
                    }

                    if ($this->connection) {
                        $factory = $factory->connection($this->connection);
                    }

                    $args = $method === RelationRequest::BelongsToMany
                        ? [$factory, $factory->mergedPivotAttributes(), $relationship]
                        : [$factory, $relationship];

                    $self = $self->$method(...$args);
                }
            }
        }

        return call_user_func($callback->bindTo($self));
    }

    protected function mergedPivotAttributes(): Closure
    {
        return function (Model $model) {
            return collect($this->pivot)->reduce(function ($merged, $pivot) use ($model) {
                return array_merge($merged, is_callable($pivot) ? call_user_func($pivot, $model) : $pivot);
            }, []);
        };
    }

    protected function newBatch(): self
    {
        $this->currentBatch++;

        return $this;
    }

    // Core method overwrites

    protected function createChildren(Model $model)
    {
        $this->withRelationsApplied(fn () => parent::createChildren($model));
    }

    protected function getRawAttributes(?Model $parent)
    {
        return $this->withRelationsApplied(fn () => parent::getRawAttributes($parent));
    }
}
