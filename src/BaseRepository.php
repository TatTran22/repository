<?php

namespace TatTran\Repository;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use TatTran\Repository\Cache\QueryCacheTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use QueryCacheTrait;

    protected $model;
    protected ?\ReflectionClass $reflection = null;

    protected function getReflection()
    {
        return $this->reflection ??= new \ReflectionClass($this->getModel());
    }

    protected function getModel()
    {
        return $this->model;
    }

    public function getInstance($object)
    {
        if (is_a($object, get_class($this->getModel()))) {
            return $object;
        } else {
            return $this->getById($object);
        }
    }

    public function getByQuery($params = [], $size = 25)
    {
        $sort = Arr::get($params, 'sort', 'created_at:-1');
        $params['sort'] = $sort;

        $query = Arr::except($params, ['page', 'limit']);
        $lModel = $this->applyFilterScope($this->getModel(), $query);

        switch ($size) {
            case -1:
                $callback = fn($query) => $query->get();
                break;
            case 0:
                $callback = fn($query) => $query->first();
                break;
            default:
                $callback = fn($query) => $query->paginate($size);
                break;
        }

        $records = $this->callWithCache(
            $callback,
            [$lModel],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getName() . '.getByQuery', Arr::dot($params)),
            $this->getModel()->defaultCacheKeys('list')
        );

        return $this->lazyLoadInclude($records);
    }

    protected function applyFilterScope(Model $model, array $params)
    {
        foreach ($params as $funcName => $funcParams) {
            $funcName = Str::studly($funcName);
            if ($this->getReflection()->hasMethod('scope' . $funcName)) {
                $funcName = lcfirst($funcName);
                $model = $model->$funcName($funcParams);
            }
        }
        return $model;
    }

    protected function getIncludes(): array
    {
        $query = app()->make(Request::class)->query();
        $includes = Arr::get($query, 'include', []);
        if (!is_array($includes)) {
            $includes = array_map('trim', explode(',', $includes));
        }
        return $includes;
    }

    protected function lazyLoadInclude($objects)
    {
        if ($this->getReflection()->hasProperty('mapLazyLoadInclude')) {
            $includes = $this->getIncludes();
            $with = call_user_func($this->getReflection()->name . '::lazyloadInclude', $includes);
            if ($objects instanceof LengthAwarePaginator) {
                return $objects->setCollection($objects->load($with));
            }
            return $objects->load($with);
        }
        return $objects;
    }

    public function getById($id, $key = 'id')
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

    public function getByIdInTrash($id, $key = 'id')
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

    public function store(array $data)
    {
        return $this->getModel()->create(Arr::only($data, $this->getModel()->getFillable()));
    }

    public function storeArray(array $datas)
    {
        if (count($datas) && is_array(reset($datas))) {
            $fillable = $this->getModel()->getFillable();
            $now = Carbon::now();

            foreach ($datas as $key => $data) {
                $datas[$key] = Arr::only($data, $fillable);
                if ($this->getModel()->usesTimestamps()) {
                    $datas[$key]['created_at'] = $now;
                    $datas[$key]['updated_at'] = $now;
                }
            }

            $result = $this->getModel()->insert($datas);

            if ($result) {
                \Cache::tags($this->getModel()->listCacheKeys('list'))->flush();
            }
            return $result;
        }

        return $this->store($datas);
    }

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

    public function delete($id)
    {
        $record = $this->getInstance($id);
        return $record->delete();
    }

    public function destroy($id)
    {
        $record = $this->getInstance($id);
        return $record->forceDelete();
    }

    public function restore($id)
    {
        $record = $this->getInstance($id);
        return $record->restore();
    }
}
