<?php

namespace Makeable\LaravelFactory\Concerns;

use Illuminate\Support\Arr;

trait PrototypesModels
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
     * @var array
     */
    protected $lazyFill = [];

    /**
     * @param array|callable $attributes
     * @return $this
     */
    public function fill($attributes)
    {
        if (is_callable($attributes)) {
            return $this->lazyFill($attributes);
        }

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
     * @param array $prototype
     * @return $this
     */
    public function mergePrototype($prototype)
    {
        $this->fill($prototype['attributes']);

        if (($states = array_get($prototype, 'activeStates')) !== null) {
            $this->states($states);
        }

        if (($amount = array_get($prototype, 'amount')) !== null) {
            $this->times($amount);
        }

        $builders = array_get($prototype, 'builders', []);

        if (method_exists($this, 'build')) {
            foreach ($builders as $builder) {
                $this->build($builder);
            }
        }
        else {
            $this->builders = array_merge($this->builders, $builders);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toPrototype()
    {
        return [
            'activeStates' => $this->activeStates,
            'amount' => $this->amount,
            'attributes' => $this->attributes,
            'builders' => $this->builders,
            'lazyFill' => $this->lazyFill,
        ];
    }

    /**
     * @param $callable
     * @return $this
     */
    protected function lazyFill($callable)
    {
        $this->lazyFill = array_merge($this->lazyFill, Arr::wrap($callable));

        return $this;
    }

//    /**
//     * @param $callable
//     * @return $this
//     */
//    protected function build($callable)
//    {
//        $this->builders = array_merge($this->builders, Arr::wrap($callable));
//
//        return $this;
//    }
}