<?php


namespace Makeable\LaravelFactory;

use Closure;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Macroable;
use Makeable\LaravelFactory\Concerns\CollectsModels;
use Makeable\LaravelFactory\Concerns\PrototypesModels;
use Makeable\LaravelFactory\Concerns\HasRelations;

class FactoryBuilder
{
    use PrototypesModels,
        HasRelations,
        Macroable;

    /**
     * The database connection on which the model instance should be persisted.
     *
     * @var string
     */
    protected $connection;

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
     * The model being built.
     *
     * @var string
     */
    protected $class;

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
        $this->name = $name;
        $this->class = $class;
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
     * @return Faker
     */
    public function fake()
    {
        return $this->faker;
    }

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
        return $this
            ->buildPrototype()
            ->buildResults([new $this->class, 'newCollection'], function () use ($attributes) {
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
        return $this
            ->buildPrototype()
            ->buildResults([Arr::class, 'wrap'], function () use ($attributes) {
                return $this->getRawAttributes($attributes);
            });
    }

    protected function buildPrototype()
    {
        $this->lazyFill = [];

        collect($this->states->getDefinition($this->class, $this->name))
            ->concat($this->states->getStates($this->class, $this->activeStates))
            ->concat($this->builders)
            ->each(function ($builder) {
                call_user_func($builder, $this); //, $this->faker
            });

        return $this;
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
        $lazyAttributes = collect($this->lazyFill)->reduce(function ($attributes, $fill) {
            return array_merge($attributes, call_user_func($fill, $this->faker));
        }, []);

        return $this->expandAttributes(
            array_merge($this->attributes, $lazyAttributes, $attributes)
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