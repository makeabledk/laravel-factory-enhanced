<?php

namespace Makeable\LaravelFactory;

use BadMethodCallException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

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
     * Attributes to apply.
     *
     * @var array
     */
    public $attributes = [];

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
        [$this->batch, $this->model] = [$batch, new $class];

        $this->parseArgs($args);

        if (! $this->path) {
            throw new BadMethodCallException(
                'Relation not found. Failed to locate any of the following strings as defined relations on model "'.get_class($this->model).'": '.
                ((count($this->states) > 0)
                    ? str_replace('""', 'NULL', '"'.implode('", "', $this->states).'"')
                    : '- NO POSSIBLE RELATION NAMES GIVEN -')
            );
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

            if (is_array($arg) && ! isset($arg[0])) {
                return $this->attributes = $arg;
            }

            if (is_callable($arg) && ! is_string($arg)) {
                return $this->builder = $arg;
            }

            if (is_string($arg) && $this->isValidRelation($arg)) {
                return $this->path = $arg;
            }

            // If nothing else, we'll assume $arg represent some state
            $this->states = array_merge($this->states, Arr::wrap($arg));
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
        $request->attributes = $this->attributes;
        $request->builder = $this->builder;
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
