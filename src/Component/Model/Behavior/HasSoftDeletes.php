<?php

declare(strict_types=1);

namespace Strux\Component\Model\Behavior;

use Strux\Component\Model\Model;

trait HasSoftDeletes
{
    /**
     * Boot the soft deleting trait for a model.
     */
    public function initializeHasSoftDeletes(): void
    {
        static::addGlobalScope('softDeletes', function (Model $builder) {
            $column = $builder->getSoftDeleteColumn();
            $builder->where($column, 'IS NULL');
        });
    }

    /**
     * Perform a soft delete on the model by overriding the parent's delete method.
     */
    public function delete(): bool
    {
        $column = $this->getSoftDeleteColumn();
        $this->{$column} = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * Restore a soft-deleted model instance.
     */
    public function restore(): bool
    {
        $column = $this->getSoftDeleteColumn();
        $this->{$column} = null;
        return $this->save();
    }

    /**
     * Determine if the model instance has been soft-deleted.
     */
    public function trashed(): bool
    {
        $column = $this->getSoftDeleteColumn();
        return $this->{$column} !== null;
    }

    /**
     * Get a new query builder that includes soft-deleted models.
     */
    public static function withTrashed(): Model
    {
        return static::query()->withoutGlobalScope('softDeletes');
    }

    /**
     * Get a new query builder that only includes soft-deleted models.
     */
    public static function onlyTrashed(): Model
    {
        $instance = static::query()->withoutGlobalScope('softDeletes');
        $column = $instance->getSoftDeleteColumn();
        return $instance->where($column, 'IS NOT NULL');
    }

    /**
     * Permanently delete the model.
     */
    public function forceDelete(): bool
    {
        // Calls the original, permanent delete method from the parent Model class.
        return parent::delete();
    }
}