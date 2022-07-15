<?php

namespace Makeable\LaravelFactory;

use BadMethodCallException;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class RelationRequest
{
    /**
     * The parent model requesting relations.
     *
     * @var Model
     */
    protected $class;

    /**
     * The batch number.
     *
     * @var int
     */
    protected $batch;

    /**
     * The given inline arguments except relation path
     * .
     *
     * @var array
     */
    protected $args;

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
     * @param $class
     * @param $batch
     * @param  mixed  $args
     */
    public function __construct($class, $batch, $args)
    {
        [$this->class, $this->batch] = [$class, $batch];

        $this->args = collect($args)
            ->pipe(Closure::fromCallable([$this, 'findAndPopRelationName']))
            ->tap(Closure::fromCallable([$this, 'failOnMissingRelation']))
            ->values()
            ->all();
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
            collect($this->args)->prepend($this->getNestedPath())
        );
    }

    /**
     * Get provided inline args.
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Get batch no.
     *
     * @return int
     */
    public function getBatch()
    {
        return $this->batch;
    }

    /**
     * Get the nested path beyond immediate relation.
     *
     * @param  string|null  $path
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

        return $this->cachedRelatedClass = $this->cachedRelatedClass
            ?: get_class($this->model()->$relation()->getRelated());
    }

    /**
     * Get the name of the immediate relation.
     *
     * @param  string|null  $path
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
     * @param  Collection  $args
     * @return Collection
     */
    protected function findAndPopRelationName(Collection $args)
    {
        return $args->reject(function ($arg) {
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
        return new $this->class;
    }

    /**
     * Fail build with a readable exception message.
     *
     * @param  Collection  $args
     */
    protected function failOnMissingRelation(Collection $args)
    {
        if (! $this->path) {
            throw new BadMethodCallException(
                'No matching relations could be found on model ['.$this->class.']. '.
                'Following possible relation names was checked: '.
                (
                    ($testedRelations = $this->getPossiblyIntendedRelationships($args))->isEmpty()
                        ? '[NO POSSIBLE RELATION NAMES FOUND]'
                        : '['.$testedRelations->implode(', ').']'
                )
            );
        }
    }

    /**
     * Give the developer a readable list of possibly args
     * that they might have intended could be a relation,
     * but was invalid. Helpful for debugging purposes.
     *
     * @param  Collection  $args
     * @return string
     */
    protected function getPossiblyIntendedRelationships(Collection $args)
    {
        return $args
            ->filter(function ($arg) {
                return is_string($arg) || is_null($arg);
            })
            ->map(function ($arg) {
                return is_null($arg) ? 'NULL' : "'".$arg."'";
            });
    }
}
