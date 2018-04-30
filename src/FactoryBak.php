<?php

namespace Makeable\LaravelFactory;

use Faker\Generator as Faker;

class FactoryBak
{
    /**
     * @var RelationManager
     */
    protected $relations;

    /**
     * @var StateManager
     */
    protected $state;

    public function __construct()
    {
        $this->relations = new RelationManager();
        $this->state = new StateManager();
    }

    public function create()
    {
        $this->relations->create($this);
    }

    /**
     * @return \Illuminate\Foundation\Application|mixed
     */
    public function faker()
    {
        return app(Faker::class);
    }

    /**
     * @param $state
     * @return FactoryBak
     */
    public function state($state)
    {
        return $this;
    }

    /**
     * @param array ...$args
     * @return FactoryBak
     */
    public function with(...$args)
    {
        $this->relations->add($args);

        return $this;
    }
}