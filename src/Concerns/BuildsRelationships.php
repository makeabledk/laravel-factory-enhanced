<?php

namespace Makeable\LaravelFactory\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Collection;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\RelationRequest;

trait BuildsRelationships
{
    /**
     * The current batch index.
     *
     * @var int
     */
    protected $currentBatch = 0;

    /**
     * Requested relations.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Requested instances to apply on relations.
     *
     * @var null | array
     */
    protected $instances;

    /**
     * Load a RelationRequest onto current FactoryBuilder.
     *
     * @param RelationRequest $request
     * @return $this
     */
    public function loadRelation(RelationRequest $request)
    {
        $factory = $this->buildFactoryForRequest($request);

        if ($request->hasNesting()) {
            // Recursively create factories until no further nesting
            $factory->with($request->createNestedRequest());
        }

        else {
            // Apply the request onto the newly created factory
            $factory->states($request->states);

            if ($request->amount) {
                $factory->times($request->amount);
            }

            if ($request->builder) {
                call_user_func($request->builder, $factory);
            }

            // Instances are stored on parent factory, not the related itself
            if ($request->instances !== null) {
                $this->instances[$request->getRelationName()][$request->batch] = $request->instances;
            }
        }

        return $this;
    }

    /**
     * Build a factory for given RelationRequest
     *
     * @param RelationRequest $request
     * @return FactoryBuilder
     */
    protected function buildFactoryForRequest($request)
    {
        $relation = $request->getRelationName();
        $batch = $request->batch;

        return data_get($this->relations, "{$relation}.{$batch}", function () use ($request, $relation, $batch) {
            return tap(app(Factory::class)->of($request->getRelatedClass()), function ($factory) use ($relation, $batch) {
                $this->relations[$relation][$batch] = $factory;
            });
        });
    }

    /**
     * Create all requested BelongsTo relations.
     *
     * @param Model $child
     */
    protected function createBelongsTo($child)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(BelongsTo::class))
            ->each(function ($batches, $relation) use ($child) {
                foreach (array_slice($batches, 0, 1) as $batch => $factory) {
                    $parent = $this->fetchFromInstancesOrCreate($relation, $batch, $factory->times(1));
                    $child->$relation()->associate($this->collectModel($parent));
                }
            });
    }

    /**
     * Create all requested BelongsToMany relations.
     *
     * @param Model $sibling
     */
    protected function createBelongsToMany($sibling)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(BelongsToMany::class))
            ->each(function ($batches, $relation) use ($sibling) {
                foreach ($batches as $batch => $factory) {
                    collect($this->fetchFromInstancesOrCreate($relation, $batch, $factory))
                        ->each(function ($model) use ($sibling, $relation, $factory) {
                            $sibling->$relation()->save($model, $this->mergeAndExpandAttributes($factory->pivotAttributes));
                        });
                };
            });
    }

    /**
     * Create all requested HasMany relations.
     *
     * @param Model $parent
     */
    protected function createHasMany($parent)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(HasOneOrMany::class))
            ->each(function ($batches, $relation) use ($parent) {
                foreach ($batches as $factory) {
                    $factory->inheritConnection($this)->create([
                        $parent->$relation()->getForeignKeyName() => $parent->$relation()->getParentKey()
                    ]);
                }
            });
    }

    /**
     * Get closure that checks for a given relation-type.
     *
     * @param $relationType
     * @return Closure
     */
    protected function relationTypeIs($relationType)
    {
        return function ($batches, $relation) use ($relationType) {
            return (new $this->class)->$relation() instanceof $relationType;
        };
    }

    /**
     * Check for given related instances or create with factory.
     *
     * @param string $relation
     * @param int $batch
     * @param FactoryBuilder $factory
     * @return Collection
     */
    protected function fetchFromInstancesOrCreate($relation, $batch, $factory)
    {
        return $factory->topUp(data_get($this->instances, "{$relation}.{$batch}"));
    }

    /**
     * Top up or slice a collection of models to reach specified amount.
     *
     * @param Collection|Model $models
     * @return Collection
     */
    protected function topUp($models)
    {
        $models = $this->collect($models);
        $targetItems = $this->amount ?? max(1, $models->count());

        if (($missing = $targetItems - $models->count()) > 0) {
            $originalAmount = $this->amount;
            $models = $models->concat($this->times($missing)->create());
            $this->amount = $originalAmount;
        }

        return $models->take($targetItems);
    }

    /**
     * Inherit connection from a parent factory.
     *
     * @param $factory
     * @return FactoryBuilder
     */
    protected function inheritConnection($factory)
    {
        if ($this->connection === null && (new $this->class)->getConnectionName() === null) {
            return $this->connection($factory->connection);
        }
    }

    /**
     * Create a new batch of relations.
     *
     * @return FactoryBuilder
     */
    protected function newBatch()
    {
        $this->currentBatch++;

        return $this;
    }
}