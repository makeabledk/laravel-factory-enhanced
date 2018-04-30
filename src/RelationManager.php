<?php

namespace Makeable\LaravelFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Support\Collection;

class RelationManager
{
    protected $batchIndex = 0;

    protected $model;

    protected $instances;

    protected $relations = [];

    public function __construct($class)
    {
        $this->model = new $class;
    }

    public function add(RelationRequest $request)
    {
        $factory = $this->makeFactory($request);

        if ($request->hasNesting()) {
            $factory->with($request->createNestedRequest());
        }
        else {
            if ($request->states !== null) {
                $factory->states($request->states);
            }

            if ($request->times !== null) {
                $factory->times($request->times);
            }

            if ($request->builder !== null) {
                call_user_func($request->builder, $factory);
            }

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
    protected function makeFactory($request)
    {
        $relation = $request->getRelationName();
        $batch = $request->batch;

        return data_get($this->relations,  "{$relation}.{$batch}", function () use ($request, $relation, $batch) {
            return tap(app(Factory::class)->of($request->getRelatedClass()), function ($factory) use ($relation, $batch) {
                $this->relations[$relation][$batch] = $factory;
            });
        });
    }

    /**
     * @return int
     */
    public function newBatch()
    {
        return $this->batchIndex++;
    }

    /**
     * @param Model $parent
     */
    public function create(Model $parent)
    {
        $relations = collect($this->relations);

        $relations
            ->filter($this->relationTypeIs(BelongsTo::class))
            ->map(function (array $batches, $relation) {
                // Check for any given instances or create with factory
                $results = array_get($this->instances, $relation, function () use ($batches) {
                    return array_first($batches)->create();
                });

                return $results instanceof Model ? $results : collect($results)->first();
            })
            ->each(function (Model $child, $relation) use ($parent) {
                $parent->$relation()->associate($child);
            })
            ->pipe(function () use ($parent) {
                $parent->save();
            });


        $relations
            ->filter($this->relationTypeIs(HasOneOrMany::class))
            ->each(function (array $batches, $relation) use ($parent) {
                foreach ($batches as $factory) {
                    $factory
                        ->fill([$parent->$relation()->getForeignKeyName() => $parent->$relation()->getParentKey()])
                        ->create();
                }
            });

//        $this->createRelationsOfType($relations, BelongsToMany::class);
//        $this->createRelationsOfType($relations, HasManyOneOrM::class);
//        $this->createRelationsOfType($relations, HasOneOrMany::class); //?

        // recursively create belongsTo
        // create model
        // create hasMany
        // create belongsToMany
    }

    protected function relationTypeIs($relationType)
    {
        return function ($batches, $relation) use ($relationType) {
            return $this->model->$relation() instanceof $relationType;
        };

    }


}