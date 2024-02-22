<?php

namespace TatTran\Repository\Cache;

use Illuminate\Support\Facades\Cache;

class FlushCache
{
    /**
     * Flush all cache tags related to query caching.
     *
     * @return void
     */
    public static function all()
    {
        Cache::tags('QueryCache')->flush();
    }

    /**
     * Flush cache tags associated with the given request tag.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public static function request($request)
    {
        Cache::tags($request->tag)->flush();
    }
}
