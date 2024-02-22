<?php

namespace TatTran\Repository\Middleware;

use Closure;
use Illuminate\Http\Request;
use TatTran\Repository\Cache\FlushCache;

class TagForRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $request->tag = uniqid('request_') . uniqid('-');
        $response = $next($request);
        FlushCache::request($request);
        return $response;
    }
}
