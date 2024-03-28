<?php

namespace TatTran\Repository;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use TatTran\Repository\Cache\QueryCacheTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use QueryCacheTrait;

    /**
     * The Eloquent model instance.
     *
     * @var Model
     */
    protected $model;

    /**
     * The ReflectionClass instance for the model.
     *
     * @var \ReflectionClass|null
     */
    private $reflection;

    /**
     * Get the ReflectionClass instance for the model.
     *
     * @return \ReflectionClass
     */
    protected function getReflection(): \ReflectionClass
    {
        if ($this->reflection) {
            return $this->reflection;
        }

        $this->reflection = new \ReflectionClass($this->getModel());
        return $this->reflection;
    }

    /**
     * Get the model instance.
     *
     * @return Model
     */
    protected function getModel()
    {
        return $this->model;
    }

    /**
     * Get an instance of the model by ID or return the object if it's already an instance.
     *
     * @param mixed $object
     * @return Model
     */
    public function getInstance($object)
    {
        if (is_a($object, get_class($this->getModel()))) {
            return $object;
        } else {
            return $this->getById($object);
        }
    }

    /**
     * Get paginated records by query parameters.
     *
     * @param array $params
     * @param int $size
     * @return LengthAwarePaginator
     */
    public function getByQuery($params = [], $size = 25)
    {
        $sort = Arr::get($params, 'sort', 'created_at:-1');
        $params['sort'] = $sort;
        $model = $this->getModel();
        $query = Arr::except($params, ['page', 'limit']);

        if (count($query)) {
            $model = $this->applyFilterScope($model, $query);
        }

        $callback = function ($query, $size) {
            switch ($size) {
                case -1:
                    return $query->get();
                case 0:
                    return $query->first();
                default:
                    return $query->paginate($size);
            }
        };

        $records = $this->callWithCache(
            $callback,
            [$model, $size],
            $this->getCacheKey(env('APP_NAME'), $model->getName() . '.getByQuery', Arr::dot($params)),
            $model->defaultCacheKeys('list')
        );

        return $this->lazyLoadInclude($records);
    }


    /**
     * Apply filter scope to the query.
     *
     * @param Model $model
     * @param array $params
     * @return Model
     */
    protected function applyFilterScope(Model $model, array $params)
    {
        foreach ($params as $funcName => $funcParams) {
            $funcName = Str::studly($funcName);
            $scopeMethod = 'scope' . $funcName;

            if ($this->getReflection()->hasMethod($scopeMethod)) {
                $model->$scopeMethod($funcParams);
            }
        }

        return $model;
    }

    /**
     * Get includes from the request query.
     *
     * @return array
     */
    protected function getIncludes(): array
    {
        $query = request()->query();
        $includes = Arr::get($query, 'include', []);

        if (!is_array($includes)) {
            $includes = array_map('trim', explode(',', $includes));
        }

        return $includes;
    }

    /**
     * Lazy load includes for the objects.
     *
     * @param mixed $objects
     * @return mixed
     */
    protected function lazyLoadInclude($objects)
    {
        if ($this->hasLazyLoadInclude()) {
            $includes = $this->getIncludes();
            $with = $this->getModel()::lazyloadInclude($includes);

            if ($objects instanceof LengthAwarePaginator) {
                return $objects->setCollection($objects->load($with));
            }

            return $objects->load($with);
        }

        return $objects;
    }

    protected function hasLazyLoadInclude(): bool
    {
        return property_exists($this->getModel(), 'mapLazyLoadInclude');
    }


    /**
     * Get a record by its ID.
     *
     * @param mixed $id
     * @param string $key
     * @return Model
     */
    public function getById($id, string $key = 'id')
    {
        if ($key == 'id' && is_numeric($id)) {
            $id = (int)$id;
        }

        $callback = function ($id, $static, $key) {
            if ($key != $static->getModel()->getKeyName()) {
                return $static->getModel()->where($key, $id)->firstOrFail();
            }
            return $static->getModel()->findOrFail($id);
        };

        $record = $this->callWithCache(
            $callback,
            [$id, $this, $key],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getName() . '.getById', [$key => $id])
        );

        return $this->lazyLoadInclude($record);
    }

    /**
     * Get a soft-deleted record by its ID.
     *
     * @param mixed $id
     * @param string $key
     * @return Model
     */
    public function getByIdInTrash($id, string $key = 'id')
    {
        if (is_numeric($id)) {
            $id = (int)$id;
        }

        $callback = function ($id, $static, $key) {
            if ($key != $static->getModel()->getKeyName()) {
                return $static->getModel()->withTrashed()->where($key, $id)->firstOrFail();
            }
            return $static->getModel()->withTrashed()->findOrFail($id);
        };

        $record = $this->callWithCache(
            $callback,
            [$id, $this, $key],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getName() . '.getByIdInTrash', [$key => $id])
        );

        return $this->lazyLoadInclude($record);
    }

    /**
     * Store a new record.
     *
     * @param array $data
     * @return Model
     */
    public function store(array $data)
    {
        return $this->getModel()->create(Arr::only($data, $this->getModel()->getFillable()));
    }

    /**
     * Store multiple records.
     *
     * @param array $data
     * @return Model
     */
    public function storeArray(array $data)
    {
        if (count($data) && is_array(reset($data))) {
            $fillable = $this->getModel()->getFillable();
            $now = \Carbon\Carbon::now();

            foreach ($data as $key => $data) {
                $data[$key] = Arr::only($data, $fillable);
                if ($this->getModel()->usesTimestamps()) {
                    $data[$key]['created_at'] = $now;
                    $data[$key]['updated_at'] = $now;
                }
            }
            $result = $this->getModel()->insert($data);
            if ($result) {
                Cache::tags($this->getModel()->listCacheKeys('list'))->flush();
            }
            return $result;
        }

        return $this->store($data);
    }

    /**
     * Update a record by its ID.
     *
     * @param mixed $id
     * @param array $data
     * @param array $excepts
     * @param array $only
     * @return Model
     */
    public function update($id, array $data, array $excepts = [], array $only = [])
    {
        $data = Arr::except($data, $excepts);
        if (count($only)) {
            $data = Arr::only($data, $only);
        }
        $record = $this->getInstance($id);

        $record->fill($data)->save();
        return $record;
    }

    /**
     * Delete a record by its ID.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id): bool
    {
        $record = $this->getInstance($id);
        return $record->delete();
    }

    /**
     * Permanently delete a record by its ID.
     *
     * @param mixed $id
     * @return bool|null
     */
    public function destroy($id)
    {
        $record = $this->getInstance($id);

        return $record->forceDelete();
    }

    /**
     * Restore a soft-deleted record by its ID.
     *
     * @param mixed $id
     * @return bool|null
     */
    public function restore($id)
    {
        $record = $this->getInstance($id);
        return $record->restore();
    }
}
