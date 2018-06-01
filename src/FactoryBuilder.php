<?php


namespace Makeable\LaravelFactory;

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Makeable\LaravelFactory\Concerns\NormalizesAttributes;
use Makeable\LaravelFactory\Concerns\HasRelations;

class FactoryBuilder
{
    use NormalizesAttributes,
        HasRelations,
        Macroable;

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * The model states.
     *
     * @var StateManager
     */
    protected $states;

    /**
     * The database connection on which the model instance should be persisted.
     *
     * @var string
     */
    protected $connection;

    /**
     * @var string
     */
    protected $name;

    /**
     * The model being built.
     *
     * @var string
     */
    protected $class;

    /**
     * @var int | null
     */
    protected $amount;

    /**
     * @var array
     */
    protected $activeStates = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Create an new builder instance.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  StateManager $states
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, $name, StateManager $states, Faker $faker)
    {
        $this->class = $class;
        $this->name = $name;
        $this->faker = $faker;
        $this->states = $states;
    }

    /**
     * Set the database connection on which the model instance should be persisted.
     *
     * @param  string  $name
     * @return $this
     */
    public function connection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * @param array|callable $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        array_push($this->attributes, $this->wrapCallable($attributes));

        return $this;
    }

    /**
     * Set the states to be applied to the model.
     *
     * @param  array|mixed  $states
     * @return $this
     */
    public function states($states)
    {
        $this->activeStates = is_array($states) ? $states : func_get_args();

        return $this;
    }

    /**
     * Set the amount of models you wish to create / make.
     *
     * @param  int  $amount
     * @return $this
     */
    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param mixed ...$args
     * @return FactoryBuilder
     */
    public function with(...$args)
    {
        $builder = new RelationRequestBuilder($this->relationsBatchIndex, $this->class);
        $builder->all(...$args)->each(function ($request) {
            $this->loadRelation($request);
        });

        return $this;
    }

    /**
     * @param mixed ...$args
     * @return FactoryBuilder
     */
    public function andWith(...$args)
    {
        $this->relationsBatchIndex++;

        return $this->with(...$args);
    }

    // _________________________________________________________________________________________________________________

    /**
     * Create a model and persist it in the database if requested.
     *
     * @param  array  $attributes
     * @return \Closure
     */
    public function lazy(array $attributes = [])
    {
        return function () use ($attributes) {
            return $this->create($attributes);
        };
    }

    /**
     * Create a collection of models and persist them to the database.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function create(array $attributes = [])
    {
        $results = $this->make($attributes);

        $this->store($results);

        return $results;
    }

    /**
     * Set the connection name on the results and store them.
     *
     * @param  \Illuminate\Support\Collection  $results
     * @return void
     */
    protected function store($results)
    {
        $this->collect($results)->each(function (Model $model) {
            if (! isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $this->createBelongsTo($model);
            $model->save();
            $this->createHasMany($model);
            $this->createBelongsToMany($model);
        });
    }

    /**
     * Create a collection of models.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function make(array $attributes = [])
    {
        return $this->buildResults([new $this->class, 'newCollection'], function () use ($attributes) {
            return $this->makeInstance($attributes);
        });
    }

    /**
     * Create an array of raw attribute arrays.
     *
     * @param  array  $attributes
     * @return mixed
     */
    public function raw(array $attributes = [])
    {
        return $this->buildResults([Arr::class, 'wrap'], function () use ($attributes) {
            return $this->getRawAttributes($attributes);
        });
    }

    /**
     * @param callable $collect
     * @param callable $item
     * @return mixed
     */
    protected function buildResults($collect, $item)
    {
        if ($this->amount === null) {
            return call_user_func($item);
        }

        if ($this->amount < 1) {
            return call_user_func($collect);
        }

        return call_user_func($collect, array_map($item, range(1, $this->amount)));
    }

    /**
     * Make an instance of the model with the given attributes.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \InvalidArgumentException
     */
    protected function makeInstance(array $attributes = [])
    {
        return Model::unguarded(function () use ($attributes) {
            $instance = new $this->class(
                $this->getRawAttributes($attributes)
            );

            if (isset($this->connection)) {
                $instance->setConnection($this->connection);
            }

            return $instance;
        });
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param  array  $attributes
     * @return mixed
     */
    protected function getRawAttributes(array $attributes = [])
    {
        return $this->expandAttributes(
            collect($this->states->getDefinition($this->class, $this->name))
                ->concat($this->attributes)
                ->concat(collect($this->activeStates)->map(function ($state) {
                    return $this->states->getState($this->class, $state);
                }))
                ->push($this->wrapCallable($attributes))
                ->reduce(function ($attributes, $generate) {
                    return array_merge($attributes, call_user_func($generate, $this->faker));
                }, [])
        );
    }

    /**
     * Expand all attributes to their underlying values.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function expandAttributes(array $attributes)
    {
        foreach ($attributes as &$attribute) {
            if (is_callable($attribute) && ! is_string($attribute)) {
                $attribute = $attribute($attributes);
            }

            if ($attribute instanceof static) {
                $attribute = $attribute->create()->getKey();
            }

            if ($attribute instanceof Model) {
                $attribute = $attribute->getKey();
            }
        }

        return $attributes;
    }
}