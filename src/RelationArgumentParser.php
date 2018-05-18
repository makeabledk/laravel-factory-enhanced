<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Arr;

class RelationArgumentParser
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $args;

    /**
     * @param mixed ...$args
     */
    public function __construct(...$args)
    {
        $this->args = collect($args);
    }

    /**
     * @param $batch
     * @param $class
     * @return \Illuminate\Support\Collection
     */
    public function get($batch, $class)
    {
       if ($this->isRelationRequest()) {
           return collect([$this->args->first()]);
       }

       if ($this->isArrayOfRelations()) {
           return $this->toRelationRequests($batch, $class, $this->args->first());
       }

       return $this->toRelationRequests($batch, $class, [$this->args->all()]);
    }

    /**
     * @return bool
     */
    protected function isRelationRequest()
    {
        return $this->args->count() === 1 && $this->args->first() instanceof RelationRequest;
    }

    /**
     * @return bool
     */
    protected function isArrayOfRelations()
    {
        return $this->args->count() === 1 && is_array($this->args->first());
    }

    /**
     * @param $batch
     * @param $class
     * @param $relations
     * @return \Illuminate\Support\Collection
     */
    protected function toRelationRequests($batch, $class, $relations)
    {
        return collect($relations)->map(function ($args, $key) use ($batch, $class) {
            $args = Arr::wrap($args);

            // Relations is an associative array and the key is the relations path
            if (! is_numeric($key)) {
                array_push($args, $key);
            }

            return new RelationRequest($batch, $class, $args);
        });
    }
}