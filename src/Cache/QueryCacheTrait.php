<?php

namespace TatTran\Repository\Cache;

use Illuminate\Support\Facades\Cache;

trait QueryCacheTrait
{
    protected $avoidCache = false;
    protected $defaultCacheTags = ['QueryCache'];

    public function cacheFor(int $seconds)
    {
        $this->avoidCache = false;
        $this->cacheTime = $seconds;
        return $this;
    }

    public function noCache()
    {
        $this->avoidCache = true;
        return $this;
    }

    public function getCacheTime()
    {
        return $this->cacheTime ?? 0;
    }

    public function getInCache()
    {
        return $this->getCacheTime() > 0 && !$this->avoidCache;
    }

    public function getCacheKey($service, $function, array $bindings)
    {
        return CacheKey::generate($service, $function, $bindings);
    }

    public function callWithCache(callable $callback, array $params, $cacheKey, array $tags = [])
    {
        $request = app()->make('request');
        $tags = array_unique(array_merge($tags, $this->defaultCacheTags, [$cacheKey, $request->tag]));

        if ($this->getInCache()) {
            return Cache::tags($tags)->remember($cacheKey, $this->getCacheTime(), function () use ($callback, $params) {
                return call_user_func_array($callback, $params);
            });
        }

        return call_user_func_array($callback, $params);
    }
}
