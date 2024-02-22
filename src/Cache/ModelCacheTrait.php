<?php

namespace TatTran\Repository\Cache;

use TatTran\Repository\Cache\CacheKey;
use TatTran\Repository\Cache\FlushCacheObserver;
use Illuminate\Database\Eloquent\Builder;

trait ModelCacheTrait
{
    protected $defaultCacheTime = 200;

    public function cacheTime(): int
    {
        return $this->defaultCacheTime;
    }

    protected static function bootModelCacheTrait(): void
    {
        static::observe(FlushCacheObserver::class);
    }

    public static function getName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get all cache keys for the given type.
     *
     * @param string $type
     * @return array
     */
    public function listCacheKeys(string $type): array
    {
        return array_unique(array_merge($this->defaultCacheKeys($type), $this->customCacheKeys($type)));
    }

    /**
     * Get the default cache keys for the given type.
     *
     * @param string $type
     * @return array
     */
    public function defaultCacheKeys(string $type): array
    {
        return match ($type) {
            'detail' => [
                CacheKey::generate(env('APP_NAME'), static::class . '.getById', ['id' => $this->id]),
                CacheKey::generate(env('APP_NAME'), static::class . '.getByIdInTrash', ['id' => $this->id])
            ],
            'list' => ['lists.' . static::class],
            default => [],
        };
    }

    /**
     * Get the custom cache keys for the given type.
     *
     * @param string $type
     * @return array
     */
    public function customCacheKeys(string $type): array
    {
        return [];
    }

    /**
     * Get a new query builder instance with cache support.
     *
     * @return QueryBuilderWithCache
     */
    protected function newBaseQueryBuilder(): QueryBuilderWithCache
    {
        $connection = $this->getConnection();
        return (new QueryBuilderWithCache($connection, $connection->getQueryGrammar(), $connection->getPostProcessor()))
            ->cacheFor($this->cacheTime())
            ->withName(static::class);
    }
}
