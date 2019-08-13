<?php

namespace Makeable\LaravelFactory;

use BadMethodCallException;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

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
     * The presets to apply.
     *
     * @var array
     */
    public $presets = [];

    /**
     * The states to apply.
     *
     * @var array
     */
    public $states = [];

    /**
     * A build function.
     *
     * @var callable | null
     */
    public $builder = null;

    /**
     * Attributes to apply.
     *
     * @var array
     */
    public $attributes = [];

    /**
     * @var StateManager
     */
    protected $stateManager;

    /**
     * Create a new relationship request.
     *
     * @param $class
     * @param $batch
     * @param StateManager $stateManager
     * @param mixed $args
     */
    public function __construct($class, $batch, StateManager $stateManager, $args)
    {
        [$this->model, $this->batch, $this->stateManager] = [new $class, $batch, $stateManager];

        $this
            ->findAndPopRelationName($args)
            ->tap(function (Collection $args) {
                // In case no matching relation found, be sure to give the
                // developer a useful exception for debugging purposes.
                if (! $this->path) {
                    $testedRelations = $this->getPossiblyIntendedRelationships($args);

                    throw new BadMethodCallException(
                        'No matching relations could be found on model ['.get_class($this->model).']. '.
                        'Following possible relation names was checked: '.
                        ($testedRelations->isEmpty()
                            ? '[NO POSSIBLE RELATION NAMES FOUND]'
                            : '['.$testedRelations->implode(', ').']')
                    );
                }
            })
            ->each(Closure::fromCallable([$this, 'parseArgument']));
    }

    /**
     * Create a new relationship request for nested relations.
     *
     * @return RelationRequest
     */
    public function createNestedRequest()
    {
        $request = new static($this->getRelatedClass(), $this->batch, $this->stateManager, $this->getNestedPath());
        $request->amount = $this->amount;
        $request->attributes = $this->attributes;
        $request->builder = $this->builder;
        $request->states = $this->states;

        return $request;
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
     * Loop through arguments to detect a relation name.
     *
     * @param mixed $args
     * @return Collection
     */
    protected function findAndPopRelationName($args)
    {
        return collect($args)->reject(function ($arg) {
            if ($match = (is_string($arg) && $this->isValidRelation($arg))) {
                $this->path = $arg;
            }

            return $match;
        });
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
     * Parse each individual argument given to 'with'.
     *
     * @param mixed $arg
     */
    protected function parseArgument($arg)
    {
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

        if ($presets = $this->parsePresets($arg)) {
            return $this->presets = $presets;
        }

        // If nothing else, we'll assume $arg represent some state.
        return $this->states = array_merge($this->states, Arr::wrap($arg));
    }

    /**
     * Attempt to parse argument as one or more presets.
     *
     * @param $presets
     * @return array|bool
     */
    protected function parsePresets($presets)
    {
        if (! is_string($presets) || is_array($presets)) {
            return false;
        }

        $presets = Arr::wrap($presets);

        foreach ($presets as $preset) {
            if (! $this->stateManager->presetExists($this->getRelatedClass(), $preset)) {
                return false;
            }
        }

        return $presets;
    }

    /**
     * Give the developer a readable list of possibly args
     * that they might have intended could be a relation,
     * but was invalid. Helpful for debugging purposes.
     *
     * @param Collection $args
     * @return string
     */
    protected function getPossiblyIntendedRelationships(Collection $args)
    {
        return $args
            ->filter(function ($arg) {
                return is_string($arg) || is_null($arg);
            })
            ->map(function ($arg) {
                return is_null($arg) ? "NULL" : "'".$arg."'";
            });
    }
}
