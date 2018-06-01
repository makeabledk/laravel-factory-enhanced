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
//    use PrototypesModels;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var int
     */
    public $batch;

    /**
     * @var string
     */
    public $path;

    /**
     * @var Collection
     */
    public $instances;

    /**
     * The number of models to build.
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
     * @var array
     */
    public $builder = null;

    /**
     * @param $batch
     * @param $class
     * @param $args
     */
    public function __construct($batch, $class, $args)
    {
        $this->batch = $batch;
        $this->model = new $class;

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
     * @return static
     */
    public function createNestedRequest()
    {
        return new static($this->batch, $this->getRelatedClass(), $this->getNestedPath());
    }

    /**
     * @param null $path
     * @return mixed
     */
    public function getRelationName($path = null)
    {
        $nested = explode('.', $path ?: $this->path);

        return array_shift($nested);
    }

    /**
     * @return Model
     */
    public function getRelatedClass()
    {
        $relation = $this->getRelationName();

        return get_class($this->model->$relation()->getRelated());
    }

    /**
     * @param null $path
     * @return string
     */
    public function getNestedPath($path = null)
    {
        $nested = explode('.', $path ?: $this->path);

        array_shift($nested);

        return implode('.', $nested);
    }

    /**
     * @return bool|int
     */
    public function hasNesting()
    {
        return strpos($this->path, '.');
    }

    /**
     * @param $path
     * @return bool
     */
    protected function isValidRelation($path)
    {
        $relation = $this->getRelationName($path);

        return method_exists($this->model, $relation) && $this->model->$relation() instanceof Relation;
    }

    /**
     * @param $arg
     * @return bool
     */
    protected function isValidState($arg)
    {
        return false;
    }
}