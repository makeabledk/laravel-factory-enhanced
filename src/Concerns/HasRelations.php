<?php

namespace Makeable\LaravelFactory\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Collection;
use Makeable\LaravelFactory\Concerns\CollectsModels;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\FactoryBuilder;
use Makeable\LaravelFactory\RelationRequest;

trait HasRelations
{
    use CollectsModels;

    /**
     * @var int
     */
    protected $relationsBatchIndex = 0;

    /**
     * @var null | array
     */
    protected $instances;

    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @param RelationRequest $request
     * @return $this
     */
    public function loadRelation(RelationRequest $request)
    {
        $factory = $this->makeFactoryForRequest($request);

        if ($request->hasNesting()) {
            $factory->with($request->createNestedRequest());
        }
        else {
            $factory->mergeAttributes($request);

            if ($request->instances !== null) {
                $this->instances[$request->getRelationName()] = $request->instances;
            }
        }

        return $this;
    }

    /**
     * @param RelationRequest $request
     * @return FactoryBuilder
     */
    protected function makeFactoryForRequest($request)
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
     * @param Model $parent
     */
    protected function createBelongsTo(Model $child)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(BelongsTo::class))
            ->map($this->fetchFromInstancesOrCreate())
            ->each(function (Model $parent, $relation) use ($child) {
                $child->$relation()->associate($parent);
            });
    }

    /**
     * @param Model $parent
     */
    protected function createHasMany(Model $parent)
    {
        collect($this->relations)
            ->filter($this->relationTypeIs(HasOneOrMany::class))
            ->each(function (array $batches, $relation) use ($parent) {
                foreach ($batches as $factory) {
                    $factory->create([
                        // TODO some conneciton stuff?
                        $parent->$relation()->getForeignKeyName() => $parent->$relation()->getParentKey()
                    ]);
                }
            });
    }

    /**
     * @param $relationType
     * @return Closure
     */
    protected function relationTypeIs($relationType)
    {
        return function ($batches, $relation) use ($relationType) {
            return $this->model->$relation() instanceof $relationType;
        };
    }

    /**
     * Check for given instances or create with factory
     *
     * @return Closure
     */
    protected function fetchFromInstancesOrCreate()
    {
        return function ($batches, $relation) {
            return $this->collectModel(
                array_get($this->instances, $relation, function () use ($batches) {
                    return array_first($batches)->create();
                })
            );
        };
    }
}