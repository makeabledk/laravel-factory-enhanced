<?php

namespace Makeable\LaravelFactory;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Makeable\LaravelFactory\Concerns\PrototypesModels;

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
     * @param $batch
     * @param $class
     * @param $args
     */
    public function __construct($batch, $class, $args)
    {
        list ($this->batch, $this->model) = [$batch, new $class];

        $this->parseArgs($args);
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

            if ($arg instanceof Closure) {
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

            if ($this->isValidState($arg)) {
                return array_push($this->states, $arg);
            }

            throw new \BadMethodCallException('Could not recognize argument '. $arg);
        });
    }

    /**
     * Create a new relationship request for nested relations.
     *
     * @return static
     */
    public function createNestedRequest()
    {
        return new static($this->batch, $this->getRelatedClass(), $this->getNestedPath());
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

    /**
     * Check if a string represents a valid state for related model.
     *
     * @param $arg
     * @return bool
     */
    protected function isValidState($arg)
    {
        return false;
    }
}