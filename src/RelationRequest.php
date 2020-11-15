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
//    use PrototypesModels;

    /**
     * The parent model requesting relations.
     *
     * @var Model
     */
    protected string $model;

    /**
     * The batch number.
     *
     * @var int
     */
    protected int $batch;

    protected Collection $arguments;

    /**
     * @var string|null
     */
    protected $cachedRelatedClass;

    /**
     * The (possibly nested) relations path.
     *
     * @var string
     */
    public $path;

    /**
     * Create a new relationship request.
     *
     * @param $model
     * @param $batch
     * @param mixed $arguments
     */
    public function __construct($model, $batch, $arguments)
    {
        [$this->model, $this->batch, $this->arguments] = [$model, $batch, collect($arguments)];

        $this->findAndPopRelationName();
        $this->failOnMissingRelation();

//        collect($arguments)
//            ->pipe(Closure::fromCallable([$this, 'findAndPopRelationName']))
//            ->tap(Closure::fromCallable([$this, 'failOnMissingRelation']));
//            ->each(Closure::fromCallable([$this, 'parseArgument']));
    }

    /**
     * Create a new relationship request for nested relations.
     *
     * @return RelationRequest
     */
    public function createNestedRequest()
    {
        return new static(
            $this->getRelatedClass(),
            $this->batch,
            $this->arguments->values()->push($this->getNestedPath())
        );
    }

    public function getArguments(): Collection
    {
        return $this->arguments;
    }

    /**
     * @return int
     */
    public function getBatch()
    {
        return $this->batch;
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

        return $this->cachedRelatedClass ??= get_class($this->model()->$relation()->getRelated());
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
     */
    protected function findAndPopRelationName()
    {
        $this->arguments->reject(function ($arg) {
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
        $model = $this->model();
        $relation = $this->getRelationName($path);

        return method_exists($model, $relation) && $model->$relation() instanceof Relation;
    }

    /**
     * @return Model
     */
    protected function model()
    {
        return new $this->model;
    }

//    /**
//     * Parse each individual argument given to 'with'.
//     *
//     * @param mixed $arg
//     * @return void
//     */
//    protected function parseArgument($arg)
//    {
//        if (is_null($arg)) {
//            return;
//        }
//
//        if (is_numeric($arg)) {
//            $this->amount = $arg;
//
//            return;
//        }
//
//        if (is_array($arg) && ! isset($arg[0])) {
//            $this->attributes = $arg;
//
//            return;
//        }
//
//        if (is_callable($arg) && ! is_string($arg)) {
//            $this->builder = $arg;
//
//            return;
//        }
//
//        if (is_string($arg) && $this->isValidRelation($arg)) {
//            $this->path = $arg;
//
//            return;
//        }
//
//        if (is_string($arg) && $this->stateManager->definitionExists($this->getRelatedClass(), $arg)) {
//            $this->definition = $arg;
//
//            return;
//        }
//
//        if ($this->stateManager->presetsExists($this->getRelatedClass(), $arg)) {
//            $this->presets = array_merge($this->presets, Arr::wrap($arg));
//
//            return;
//        }
//
//        // If nothing else, we'll assume $arg represent some state.
//        return $this->states = array_merge($this->states, Arr::wrap($arg));
//    }

    /**
     * Fail build with a readable exception message.
     */
    protected function failOnMissingRelation()
    {
        if (! $this->path) {
            throw new BadMethodCallException(
                'No matching relations could be found on model ['.$this->model.']. '.
                'Following possible relation names was checked: '.
                (
                    ($testedRelations = $this->getPossiblyIntendedRelationships())->isEmpty()
                        ? '[NO POSSIBLE RELATION NAMES FOUND]'
                        : '['.$testedRelations->implode(', ').']'
                )
            );
        }
    }

    /**
     * Give the developer a readable list of possibly arguments
     * that they might have intended could be a relation,
     * but was invalid. Helpful for debugging purposes.
     *
     * @return Collection
     */
    protected function getPossiblyIntendedRelationships()
    {
        return $this->arguments
            ->filter(function ($arg) {
                return is_string($arg) || is_null($arg);
            })
            ->map(function ($arg) {
                return is_null($arg) ? 'NULL' : "'".$arg."'";
            });
    }
}
