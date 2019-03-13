<?php


namespace Makeable\LaravelFactory;

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Makeable\LaravelFactory\Concerns\NormalizesAttributes;
use Makeable\LaravelFactory\Concerns\BuildsRelationships;

class FactoryBuilder
{
    use BuildsRelationships,
        NormalizesAttributes,
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
     * Name of the definition.
     *
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
     * The number of models to build.
     *
     * @var int | null
     */
    protected $amount;

    /**
     * The states to apply.
     *
     * @var array
     */
    protected $activeStates = [];

    /**
     * Attributes to apply.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Attributes to apply to a pivot relation.
     *
     * @var array
     */
    protected $pivotAttributes = [];

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
     * Fill attributes on the model.
     *
     * @param array|callable $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        array_push($this->attributes, $this->wrapCallable($attributes));

        return $this;
    }

    /**
     * Fill attributes on the pivot model.
     *
     * @param array|callable $attributes
     * @return $this
     */
    public function fillPivot($attributes)
    {
        array_push($this->pivotAttributes, $this->wrapCallable($attributes));

        return $this;
    }

    /**
     * Set the state to be applied to the model.
     *
     * @param  string  $state
     * @return $this
     */
    public function state($state)
    {
        return $this->states([$state]);
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
     * Pass the builder to the given callback and then return it.
     *
     * @param callable $callback
     * @return $this
     */
    public function tap($callback)
    {
        call_user_func($callback, $this);

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
     * Apply the callback if the value is truthy.
     *
     * @param bool $value
     * @param callable $callback
     * @param callable|null $default
     * @return $this
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            call_user_func($callback, $this, $value);
        } elseif ($default) {
            call_user_func($default, $this, $value);
        }

        return $this;
    }

    /**
     * Build the model with specified relations.
     *
     * @param mixed ...$args
     * @return FactoryBuilder
     */
    public function with(...$args)
    {
        $builder = new RelationRequestBuilder($this->class, $this->currentBatch);
        $builder->all(...$args)->each(function ($request) {
            $this->loadRelation($request);
        });

        return $this;
    }

    /**
     * Build relations in a new batch (not belongs-to). Multiple
     * batches can be created on the same relation, and so this
     * way we may keep them from overwriting each other.
     *
     * @param mixed ...$args
     * @return FactoryBuilder
     */
    public function andWith(...$args)
    {
        return $this->newBatch()->with(...$args);
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

            $this->callAfter('creating', $model);
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
     * Build the results to either a single item or collection of items.
     *
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

            return tap($instance, function ($instance) {
                $this->callAfter('making', $instance);
            });
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
        return collect($this->states->getDefinition($this->class, $this->name))
            ->concat($this->attributes)
            ->concat(collect($this->activeStates)->map(function ($state) {
                return $this->states->getState($this->class, $state);
            }))
            ->push($this->wrapCallable($attributes))
            ->pipe(function ($attributes) {
                return $this->mergeAndExpandAttributes($attributes);
            });
    }

    /**
     * Run attribute closures, merge resulting attributes
     * and finally expand to their underlying values
     *
     * @param Collection|array $attributes
     * @return array
     */
    protected function mergeAndExpandAttributes($attributes)
    {
        return $this->expandAttributes(
            collect($attributes)->reduce(function ($attributes, $generate) {
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

    /**
     * Call after callbacks for each model and state.
     *
     * @param  string  $action
     * @param  Model  $model
     * @return void
     */
    protected function callAfter($action, $model)
    {
        $states = array_merge([$this->name], $this->activeStates);

        foreach ($states as $state) {
            $this->callAfterCallbacks($action, $model, $state);
        }
    }

    /**
     * Call after callbacks for each model and state.
     *
     * @param  string  $action
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $state
     * @return void
     */
    protected function callAfterCallbacks($action, $model, $state)
    {
        $callbacks = call_user_func([$this->states, Str::camel('get_after_'.$action.'_callbacks')], $this->class, $state);

        foreach ($callbacks as $callback) {
            $callback($model, $this->faker);
        }
    }
}