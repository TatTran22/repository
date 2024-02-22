<?php

namespace TatTran\Repository\Cache;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;

class QueryBuilderWithCache extends QueryBuilder
{
    protected $cacheTime;
    protected $modelName;
    protected $defaultCacheTags = ['QueryCache'];

    public function __construct(
        ConnectionInterface $connection,
        Grammar $grammar = null,
        Processor $processor = null
    ) {
        parent::__construct($connection, $grammar, $processor);
    }

    public function cacheFor(int $cacheTime)
    {
        $this->cacheTime = $cacheTime;
        return $this;
    }

    public function withName(string $modelName)
    {
        $this->modelName = $modelName;
        return $this;
    }

    public function getCacheTime()
    {
        return $this->cacheTime;
    }

    public function cacheKey()
    {
        return md5(vsprintf('%s.%s.%s', [
            $this->toSql(),
            json_encode($this->getBindings(), true),
            !$this->useWritePdo,
        ]));
    }

    protected function runSelect()
    {
        if ($this->cacheTime && app('request')->tag) {
            $tags = array_unique(array_merge(
                [
                    app('request')->tag,
                    $this->modelName . '_' . app('request')->tag,
                    $this->cacheKey()
                ],
                $this->defaultCacheTags
            ));

            $cacheTime = $this->getCacheTime();
            $this->cacheTime = null;
            return Cache::tags($tags)->remember($this->cacheKey(), $cacheTime, function () {
                return parent::runSelect();
            });
        }

        return parent::runSelect();
    }
}
