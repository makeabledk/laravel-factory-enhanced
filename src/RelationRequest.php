<?php

namespace Makeable\LaravelFactory;

use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class RelationRequest
{
    public const HasMany = 'has';
    public const BelongsToMany = 'hasAttached';
    public const BelongsTo = 'for';

    /**
     * The parent model requesting relations.
     */
    protected string $model;

    public int $batch;

    public Collection $arguments;

    protected ?Relation $cachedRelation;

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
     * @param  mixed  $arguments
     */
    public function __construct($model, $batch, $arguments)
    {
        [$this->model, $this->batch, $this->arguments] = [$model, $batch, collect($arguments)];

        $this->extractRelationFromArguments();
        $this->failOnMissingRelation();
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
        return get_class($this->getRelation()->getRelated());
    }

    protected function getRelation()
    {
        $relation = $this->getRelationName();

        return $this->cachedRelation ??= $this->newModel()->$relation();
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

    public function loadMethod()
    {
        if ($this->getRelation() instanceof BelongsToMany) {
            return static::BelongsToMany;
        }

        if ($this->getRelation() instanceof BelongsTo) {
            return static::BelongsTo;
        }

        if ($this->getRelation() instanceof HasOneOrMany) {
            return static::HasMany;
        }

        throw new BadMethodCallException('Unsupported relation type '.get_class($this->getRelation()));
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
    protected function extractRelationFromArguments()
    {
        $this->arguments = $this->arguments->reject(function ($arg) {
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
        $model = $this->newModel();
        $relation = $this->getRelationName($path);

        return method_exists($model, $relation) && $model->$relation() instanceof Relation;
    }

    /**
     * @return Model
     */
    protected function newModel()
    {
        return new $this->model;
    }

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
