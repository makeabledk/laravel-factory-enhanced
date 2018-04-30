<?php

namespace Makeable\LaravelFactory;

use Closure;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FactoryBuilder extends \Illuminate\Database\Eloquent\FactoryBuilder
{
    /**
     * @var array
     */
    protected $relations = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Create an new builder instance.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  array  $definitions
     * @param  array  $states
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct($class, $name, array $definitions, array $states, Faker $faker)
    {
        parent::__construct(...func_get_args());

        $this->relations = new RelationManager($class);
    }

    /**
     * @return Faker
     */
    public function fake()
    {
        return $this->faker;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * Get a raw attributes array for the model.
     *
     * @param  array  $attributes
     * @return mixed
     */
    protected function getRawAttributes(array $attributes = [])
    {
        $definition = call_user_func(
            data_get($this->definitions, "{$this->class}.{$this->name}") ?: function () {
                return [];
            },
            $this->faker, $attributes
        );

        return $this->expandAttributes(
            array_merge($this->applyStates($definition, $attributes), $this->attributes, $attributes)
        );
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
     * Set the connection name on the results and store them.
     *
     * @param  \Illuminate\Support\Collection  $results
     * @return void
     */
    protected function store($results)
    {
        $results->each(function (Model $model) {
            if (! isset($this->connection)) {
                $model->setConnection($model->newQueryWithoutScopes()->getConnection()->getName());
            }

            $this->relations->create($model);
        });
    }

    /**
     * @param array $args
     * @return FactoryBuilder
     */
    public function with(...$args)
    {
        if ($args[0] instanceof RelationRequest) {
            $this->relations->add($args[0]);
            return $this;
        }
//
//        if (empty($relations[0])) {
//            return $this;
//        }
//
        if (count($args) === 1 && is_array($args[0])) {
            $relations = $args[0]; // An associative array was given
        }
        else {
            $relations = [$args]; // Separate args was given
        }

        $batch = $this->relations->newBatch();

        collect($relations)->each(function ($args, $key) use ($batch) {
            $args = Arr::wrap($args);

            // Relations is an associative array and the key is the relations path
            if (! is_numeric($key)) {
                array_push($args, $key);
            }

            $this->relations->add(new RelationRequest($batch, $this->class, $args));
        });

        return $this;
    }
}