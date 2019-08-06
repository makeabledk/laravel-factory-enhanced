<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Arr;

class RelationRequestBuilder
{
    /**
     * The current batch.
     *
     * @var int
     */
    protected $batch;

    /**
     * The parent model requesting relations.
     *
     * @var string
     */
    protected $class;

    /**
     * Create a new request builder.
     *
     * @param $class
     * @param $batch
     */
    public function __construct($class, $batch)
    {
        $this->class = $class;
        $this->batch = $batch;
    }

    /**
     * Get a collection of RelationRequests.
     *
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
     * Check if a RelationRequest was passed.
     *
     * @param array $args
     * @return bool
     */
    protected function isRelationRequest($args)
    {
        return count($args) === 1 && $args[0] instanceof RelationRequest;
    }

    /**
     * Check if an array with multiple relationships was passed.
     *
     * Eg. ['relation_1' => ...$args, 'relations_2' => ...$args]
     *
     * @param array $args
     * @return bool
     */
    protected function isArrayOfRelationArgs($args)
    {
        return count($args) === 1 && is_array($args[0]);
    }

    /**
     * Parse normalized args to RelationRequests.
     *
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

            return new RelationRequest($this->class, $this->batch, $args);
        });
    }
}
