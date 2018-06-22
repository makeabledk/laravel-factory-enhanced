<?php

namespace Makeable\LaravelFactory;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RelationRequest
{
    /**
     * The parent model requesting relations.
     *
     * @var Model
     */
    protected $model;

    /**
     * The batch number.
     *
     * @var int
     */
    public $batch;

    /**
     * The (possibly nested) relations path.
     *
     * @var string
     */
    public $path;

    /**
     * The bound instances.
     *
     * @var Collection
     */
    public $instances;

    /**
     * The number of related models to build.
     *
     * @var int|null
     */
    public $amount;

    /**
     * The states to apply.
     *
     * @var array
     */
    public $states = [];

    /**
     * A build function.
     *
     * @var array
     */
    public $builder = null;

    /**
     * Create a new relationship request.
     *
     * @param $class
     * @param $batch
     * @param $args
     * @throws Exception
     */
    public function __construct($class, $batch, $args)
    {
        list ($this->batch, $this->model) = [$batch, new $class];

        $this->parseArgs($args);

        if (! $this->path) {
            throw new Exception('No matching relation was found on class '.get_class($this->model));
        }
    }

    /**
     * Parse the arguments given to 'with' .
     *
     * @param array $args
     */
    protected function parseArgs($args)
    {
        collect($args)->each(function ($arg) {
            if (is_numeric($arg)) {
                return $this->amount = $arg;
            }

            if (is_callable($arg) && ! is_string($arg)) {
                return $this->builder = $arg;
            }

            if ($arg instanceof Model) {
                return $this->instances = collect([$arg]);
            }

            if ($arg instanceof Collection) {
                return $this->instances = $arg;
            }

            if ($this->isValidRelation($arg)) {
                return $this->path = $arg;
            }

            // If nothing else, we'll assume $arg represent some state
            array_push($this->states, $arg);
        });
    }

    /**
     * Create a new relationship request for nested relations.
     *
     * @return static
     */
    public function createNestedRequest()
    {
        $request = new static($this->getRelatedClass(), $this->batch, $this->getNestedPath());
        $request->amount = $this->amount;
        $request->builder = $this->builder;
        $request->instances = $this->instances;
        $request->states = $this->states;

        return $request;

    }

    /**
     * Get the class name of the related eloquent model.
     *
     * @return string
     */
    public function getRelatedClass()
    {
        $relation = $this->getRelationName();

        return get_class($this->model->$relation()->getRelated());
    }

    /**
     * Get the nested path beyond immediate relation.
     *
     * @param string|null $path
     * @return string
     */
    public function getNestedPath($path = null)
    {
        $nested = explode('.', $path ?: $this->path);

        array_shift($nested);

        return implode('.', $nested);
    }

    /**
     * Get the name of the immediate relation.
     *
     * @param string|null $path
     * @return mixed
     */
    public function getRelationName($path = null)
    {
        $nested = explode('.', $path ?: $this->path);

        return array_shift($nested);
    }

    /**
     * Check if has nesting.
     *
     * @return bool
     */
    public function hasNesting()
    {
        return strpos($this->path, '.') !== false;
    }

    /**
     * Check if a string represents a valid relation path.
     *
     * @param $path
     * @return bool
     */
    protected function isValidRelation($path)
    {
        $relation = $this->getRelationName($path);

        return method_exists($this->model, $relation) && $this->model->$relation() instanceof Relation;
    }
}