<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Support\Arr;

trait HasPrototypeAttributes
{
    /**
     * The name of the model being built.
     *
     * @var string
     */
    protected $name;

    /**
     * The number of models to build.
     *
     * @var int|null
     */
    protected $amount;

    /**
     * The states to apply.
     *
     * @var array
     */
    protected $activeStates = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $builders = [];

    /**
     * @param $callable
     * @return $this
     */
    public function buildWith($callable)
    {
        $this->builders = array_merge($this->builders, Arr::wrap($callable));

        return $this;
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
     * Set the states to be applied to the model.
     *
     * @param  array|mixed  $states
     * @return $this
     */
    public function states($states)
    {
        $this->activeStates = is_array($states) ? $states : func_get_args();

        return $this;
    }

    /**
     * Set the amount of models you wish to create / make.
     *
     * @param  int  $amount
     * @return $this
     */
    public function times($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param static $other
     * @return $this
     */
    protected function mergeAttributes($other)
    {
        $this->buildWith($other->builders);
        $this->fill($other->attributes);

        if ($other->states !== null) {
            $this->states($other->states);
        }

        if ($other->amount !== null) {
            $this->times($other->amount);
        }

        return $this;
    }
}