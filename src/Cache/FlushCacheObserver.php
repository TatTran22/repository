<?php

namespace TatTran\Repository\Cache;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FlushCacheObserver
{
    /**
     * Handle the Model events.
     *
     * @param Model $model
     * @return void
     * @throws BindingResolutionException
     */
    public function created(Model $model)
    {
        $this->flushCache($model, 'list');
    }

    /**
     * @param Model $model
     * @return void
     * @throws BindingResolutionException
     */
    public function updated(Model $model)
    {
        $this->flushCache($model, 'detail');
        $this->flushCache($model, 'list');
    }

    /**
     * @param Model $model
     * @return void
     * @throws BindingResolutionException
     */
    public function deleted(Model $model)
    {
        $this->flushCache($model, 'detail');
        $this->flushCache($model, 'list');
    }

    /**
     * @param Model $model
     * @return void
     * @throws BindingResolutionException
     */
    public function forceDeleted(Model $model)
    {
        $this->flushCache($model, 'detail');
        $this->flushCache($model, 'list');
    }

    /**
     * @param Model $model
     * @return void
     * @throws BindingResolutionException
     */
    public function restored(Model $model)
    {
        $this->flushCache($model, 'detail');
        $this->flushCache($model, 'list');
    }

    /**
     * Flush cache for the given model and cache type.
     *
     * @param Model $model
     * @param string $type
     * @return void
     * @throws BindingResolutionException
     */
    protected function flushCache(Model $model, string $type)
    {
        $tags = $model->listCacheKeys($type);
        $tags[] = $model->getName() . '_' . app()->make('request')->tag;
        Cache::tags($tags)->flush();
    }
}
