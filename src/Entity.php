<?php

namespace TatTran\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use TatTran\Repository\Cache\ModelCacheTrait;

class Entity extends Model implements EntityInterface
{
    use ModelCacheTrait;

    /**
     * Acl groups allow action.
     * Example:
     * [
     *   'view' => ['admin', 'accountance'],
     *   'create' => ['admin', 'saler'],
     *   'update' => ['admin'],
     *   'delete' => ['admin']
     * ]
     *
     * @var array
     */
    public static $permissions = [];

    /**
     * Get all allowed columns for the entity.
     *
     * @return array
     */
    public function getAllowedColumns()
    {
        $timestampColumns = [];

        if ($this->usesTimestamps()) {
            $timestampColumns[] = $this->getCreatedAtColumn();
            $timestampColumns[] = $this->getUpdatedAtColumn();
        }

        return array_merge([$this->getKeyName()], $this->getFillable(), $timestampColumns);
    }

    /**
     * Scope a query to order the results.
     *
     * @param Builder $query
     * @param string $sort
     * @return Builder
     */
    public function scopeSort($query, $sort = null)
    {
        if (is_null($sort)) {
            $sort = $this->usesTimestamps() ? 'created_at:-1' : 'id:-1';
        }

        $columns = $this->getAllowedColumns();
        $sorts = explode(',', $sort);

        foreach ($sorts as $sort) {
            list($field, $type) = array_pad(explode(':', $sort), 2, 1);

            if (in_array($field, $columns)) {
                $query->orderBy($this->getTable() . '.' . $field, $type == 1 ? 'ASC' : 'DESC');
            }
        }

        return $query;
    }

    /**
     * Generate code for the entity.
     *
     * @param string|null $prefix
     * @param string $attributes
     * @return void
     */
    protected function generateCode($prefix = null, $attributes = 'code')
    {
        $this->$attributes = Code::generate($this->id, $prefix);
        $this->save();
    }

    /**
     * Get the class name of the entity.
     *
     * @return string
     */
    public static function getName()
    {
        return class_basename(static::class);
    }

    /**
     * Generate lazy load includes for the entity.
     *
     * @param array $includes
     * @return array
     */
    public static function lazyloadInclude(array $includes)
    {
        $with = [];

        foreach ($includes as $include) {
            if (isset(static::$mapLazyLoadInclude[$include])) {
                $with[] = static::$mapLazyLoadInclude[$include];
            }
        }

        return $with;
    }
}
