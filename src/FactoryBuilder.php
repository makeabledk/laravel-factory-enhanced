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
        (new RelationArgumentParser(...$args))
            ->get($this->relations->getBatch(), $this->class)
            ->each(function ($request) {
                $this->relations->add($request);
            });

        return $this;
    }

    public function andWith(...$args)
    {
        $this->relations->newBatch();

        return $this->with(...$args);
    }
}