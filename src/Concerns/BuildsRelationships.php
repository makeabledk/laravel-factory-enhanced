<?php

namespace Makeable\LaravelFactory\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\RelationRequest;

trait BuildsRelationships
{
    /**
     * The current batch no.
     */
    protected int $currentBatch = 0;

    /**
     * Requested relations.
     */
    protected array $relations = [];

    /**
     * Load a RelationRequest onto current FactoryBuilder.
     *
     * @param  \Makeable\LaravelFactory\RelationRequest  $request
     * @return \Makeable\LaravelFactory\Concerns\BuildsRelationships
     */
    public function loadRelation(RelationRequest $request): self
    {
        $factory = $this->stashRelatedFactory($request);

        // Recursively create factories until no further nesting.
        if ($request->hasNesting()) {
            return $this->loadRelation($request->createNestedRequest());
        }

        // Apply the request onto the final relationship factory.
        $this->stashRelatedFactory($request, $factory->apply(...$request->arguments));

        return $this;
    }

    protected function stashRelatedFactory(RelationRequest $request, self $factory = null): Factory
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

//    protected function buildFactoryForRequest(RelationRequest $request): Factory
//    {
//        $path = implode('.', [
//            $request->loadMethod(),
//            $request->getRelationName(),
//            $request->batch,
//        ]);
//
//        return data_get($this->relations, $path) ?? tap(static::factoryForModel($request->getRelatedClass()), function ($factory) use ($path) {
//            data_set($this->relations, $path, $factory);
//        });
//    }

    protected function withRelationsApplied(Closure $callback)
    {
//        $previous = [$this->has, $this->for];

        $self = $this;

        foreach ($this->relations as $method => $relations) {
            foreach ($relations as $relationship => $factories) {
                foreach ($factories as $batch => $factory) {
                    $args = $method === RelationRequest::BelongsToMany
                        ? [$factory, [], $relationship] // , $factory->pivotAttributes()
                        : [$factory, $relationship];

                    $self = $self->$method(...$args);
                }
            }
        }

        return call_user_func($callback->bindTo($self));

//        return tap($callback->bindTo($self), fn () => [$this->has, $this->for] = $previous);
    }

//
//    /**
//     * Create all requested BelongsTo relations.
//     *
//     * @param Model $child
//     */
//    protected function createBelongsTo($child)
//    {
//        collect($this->relations)
//            ->filter($this->relationTypeIs(BelongsTo::class))
//            ->each(function ($batches, $relation) use ($child) {
//                foreach (array_slice($batches, 0, 1) as $factory) {
//                    $parent = $this->collectModel($factory->inheritConnection($this)->create());
//                    $child->$relation()->associate($parent);
//                }
//            });
//    }
//
//    /**
//     * Create all requested BelongsToMany relations.
//     *
//     * @param Model $sibling
//     */
//    protected function createBelongsToMany($sibling)
//    {
//        collect($this->relations)
//            ->filter($this->relationTypeIs(BelongsToMany::class))
//            ->each(function ($batches, $relation) use ($sibling) {
//                foreach ($batches as $factory) {
//                    $models = $this->collect($factory->inheritConnection($this)->create());
//                    $models->each(function ($model) use ($sibling, $relation, $factory) {
//                        $sibling->$relation()->save($model, $this->mergeAndExpandAttributes($factory->pivotAttributes));
//                    });
//                }
//            });
//    }
//
//    /**
//     * Create all requested HasMany relations.
//     *
//     * @param Model $parent
//     */
//    protected function createHasMany($parent)
//    {
//        collect($this->relations)
//            ->filter($this->relationTypeIs(HasOneOrMany::class))
//            ->each(function ($batches, $relation) use ($parent) {
//                foreach ($batches as $factory) {
//                    // In case of morphOne / morphMany we'll need to set the morph type as well.
//                    if (($morphRelation = $this->newRelation($relation)) instanceof MorphOneOrMany) {
//                        $factory->fill([
//                            $morphRelation->getMorphType() => (new $this->class)->getMorphClass(),
//                        ]);
//                    }
//
//                    $factory->inheritConnection($this)->create([
//                        $parent->$relation()->getForeignKeyName() => $parent->$relation()->getParentKey(),
//                    ]);
//                }
//            });
//    }

//    /**
//     * Get closure that checks for a given relation-type.
//     *
//     * @param $relationType
//     * @return Closure
//     */
//    protected function relationTypeIs($relationType)
//    {
//        return function ($batches, $relation) use ($relationType) {
//            return $this->newRelation($relation) instanceof $relationType;
//        };
//    }
//
//    /**
//     * @param $relationName
//     * @return Relation
//     */
//    protected function newRelation($relationName)
//    {
//        return $this->newModel()->$relationName();
//    }

//    /**
//     * Inherit connection from a parent factory.
//     *
//     * @param $factory
//     * @return FactoryBuilder
//     */
//    protected function inheritConnection($factory)
//    {
//        if ($this->connection === null && (new $this->class)->getConnectionName() === null) {
//            return $this->connection($factory->connection);
//        }
//    }

    protected function newBatch(): self
    {
        $this->currentBatch++;

        return $this;
    }
}
