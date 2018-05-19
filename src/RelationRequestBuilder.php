<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Arr;

class RelationRequestBuilder
{
    /**
     * @var int
     */
    protected $batch;

    /**
     * @var string
     */
    protected $class;

    /**
     * @param $batch
     * @param $class
     */
    public function __construct($batch, $class)
    {
        $this->batch = $batch;
        $this->class = $class;
    }

    /**
     * @param mixed ...$args
     * @return \Illuminate\Support\Collection
     */
    public function all(...$args)
    {
       if ($this->isRelationRequest($args)) {
           return collect([$args[0]]);
       }

       if ($this->isArrayOfRelationArgs($args)) {
           return $this->toRelationRequests($args[0]);
       }

       return $this->toRelationRequests([$args]);
    }

    /**
     * @param array $args
     * @return bool
     */
    protected function isRelationRequest($args)
    {
        return count($args) === 1 && $args[0] instanceof RelationRequest;
    }

    /**
     * @param array $args
     * @return bool
     */
    protected function isArrayOfRelationArgs($args)
    {
        return count($args) === 1 && is_array($args[0]);
    }

    /**
     * @param array $relations
     * @return \Illuminate\Support\Collection
     */
    protected function toRelationRequests($relations)
    {
        return collect($relations)->map(function ($args, $key) {
            $args = Arr::wrap($args);

            // Relations is an associative array and the key is the relations path
            if (! is_numeric($key)) {
                array_push($args, $key);
            }

            return new RelationRequest($this->batch, $this->class, $args);
        });
    }
}