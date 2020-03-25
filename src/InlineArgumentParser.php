<?php

namespace Makeable\LaravelFactory;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class InlineArgumentParser
{
    /**
     * @param  array  $args
     * @param  \Makeable\LaravelFactory\FactoryBuilder  $builder
     * @return void
     */
    public function apply(array $args, FactoryBuilder $builder)
    {
        foreach ($args as $arg) {
            $this->applyArgument($arg, $builder);
        }
    }

    /**
     * Parse each individual argument given to 'with'.
     *
     * @param  mixed  $arg
     * @param  \Makeable\LaravelFactory\FactoryBuilder  $builder
     * @return void
     */
    protected function applyArgument($arg, FactoryBuilder $builder)
    {
        if (is_null($arg)) {
            return;
        }

        if (is_numeric($arg)) {
            $builder->times($arg);

            return;
        }

        if (is_array($arg) && ! isset($arg[0])) {
            $pivotAttributes = [];

            foreach ($arg as $attribute => $value) {
                if (Str::startsWith($attribute, 'pivot.')) {
                    $pivotAttributes[Str::after($attribute, 'pivot.')] = $value;

                    Arr::forget($arg, $attribute);
                }
            }

            $builder->fill($arg);
            $builder->fillPivot($pivotAttributes);

            return;
        }

        if (is_callable($arg) && ! is_string($arg)) {
            $builder->tap($arg);

            return;
        }

        if (is_string($arg) && $builder->stateManager->definitionExists($builder->class, $arg)) {
            $builder->definition($arg);

            return;
        }

        if ($builder->stateManager->presetsExists($builder->class, $arg)) {
            $builder->presets(array_merge($builder->presets, Arr::wrap($arg)));

            return;
        }

        // If nothing else, we'll assume $arg represent some state.
        $builder->states(array_merge($builder->states, Arr::wrap($arg)));
    }
}
