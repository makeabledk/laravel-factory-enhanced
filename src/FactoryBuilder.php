<?php

namespace Makeable\LaravelFactory;

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use Makeable\LaravelFactory\Concerns\BuildsRelationships;
use Makeable\LaravelFactory\Concerns\NormalizesAttributes;

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
    protected $stateManager;

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
    protected $states = [];

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
     * The model after making callbacks.
     *
     * @var array
     */
    protected $afterMaking = [];

    /**
     * The model after creating callbacks.
     *
     * @var array
     */
    protected $afterCreating = [];

    /**
     * Create an new builder instance.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  StateManager $stateManager
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, $name, StateManager $stateManager, Faker $faker)
    {
        $this->class = $class;
        $this->name = $name;
        $this->faker = $faker;
        $this->stateManager = $stateManager;
        $this->afterMaking = $stateManager->afterMaking;
        $this->afterCreating = $stateManager->afterCreating;
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
     * Apply the callback given certain odds are met.
     *
     * Example odds: 50, '50%', 1/2
     *
     * @param mixed $odds
     * @param callable $callback
     * @param callable|null $default
     * @return $this
     */
    public function odds($odds, $callback, $default = null)
    {
        if (is_string($odds)) {
            $odds = intval($odds);
        }

        if (is_numeric($odds) && $odds >= 0 && $odds <= 1) {
            $odds = $odds * 100;
        }

        return $this->when(rand(0, 100) <= $odds, $callback, $default);
    }

    /**
     * Apply one or more presets to the model.
     *
     * @param $preset
     * @return $this
     */
    public function preset($preset)
    {
        return $this->presets($preset);
    }

    /**
     * Apply one or more presets to the model.
     *
     * @param $presets
     * @return $this
     */
    public function presets($presets)
    {
        $presets = is_array($presets) ? $presets : func_get_args();

        foreach ($presets as $preset) {
            $this->tap($this->stateManager->getPreset($this->class, $preset));
        }

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
        $this->states = is_array($states) ? $states : func_get_args();

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
        call_user_func($callback, $this, $this->faker);

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
        if (count($args) === 1 && $args[0] instanceof RelationRequest) {
            return tap($this)->loadRelation($args[0]);
        }

        return tap($this)->loadRelation(
            new RelationRequest($this->class, $this->currentBatch, $this->stateManager, $args)
        );
    }

    /**
     * Build relations in a new batch. Multiple batches can be
     * created on the same relation, so that ie. multiple
     * has-many relations can be configured differently.
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
            $this->callAfterCreating($model);
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
                $this->callAfterMaking($instance);
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
        return collect($this->stateManager->getDefinition($this->class, $this->name))
            ->concat($this->attributes)
            ->concat(collect($this->states)->filter()->map(function ($state) {
                return $this->stateManager->getState($this->class, $state);
            }))
            ->push($this->wrapCallable($attributes))
            ->pipe(function ($callables) use ($attributes) {
                return $this->mergeAndExpandAttributes($callables, $attributes);
            });
    }

    /**
     * Run attribute closures, merge resulting attributes
     * and finally expand to their underlying values.
     *
     * @param Collection|array $attributes
     * @param array $inlineAttributes
     * @return array
     */
    protected function mergeAndExpandAttributes($attributes, array $inlineAttributes = [])
    {
        return $this->expandAttributes(
            collect($attributes)->reduce(function ($attributes, $generate) use ($inlineAttributes) {
                return array_merge($attributes, call_user_func($generate, $this->faker, $inlineAttributes));
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
     * Run after making callbacks on a collection of models.
     *
     * @param $model
     */
    protected function callAfterMaking($model)
    {
        $this->callAfter($this->afterMaking, $model);
    }

    /**
     * Run after creating callbacks on a collection of models.
     *
     * @param $model
     */
    protected function callAfterCreating($model)
    {
        $this->callAfter($this->afterCreating, $model);
    }

    /**
     * Call after callbacks for each state on model.
     *
     * @param array $afterCallbacks
     * @param Model $model
     * @return void
     */
    protected function callAfter(array $afterCallbacks, $model)
    {
        $states = array_merge([$this->name], $this->states);

        foreach ($states as $state) {
            $callbacks = data_get($afterCallbacks, "{$this->class}.{$state}", []);

            foreach ($callbacks as $callback) {
                call_user_func($callback, $model, $this->faker);
            }
        }
    }
}
