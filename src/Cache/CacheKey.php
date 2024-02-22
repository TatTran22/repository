<?php

namespace TatTran\Repository\Cache;

use Illuminate\Support\Str;

class CacheKey
{
    /**
     * Generate a cache key based on the provided service, function, and bindings.
     *
     * @param  string  $service
     * @param  string  $function
     * @param  array  $bindings
     * @return string
     */
    public static function generate($service, $function, array $bindings)
    {
        // Convert the array of bindings to a JSON string
        $bindingsJson = json_encode($bindings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Check if JSON encoding failed
        if ($bindingsJson === false) {
            throw new \RuntimeException('Failed to JSON encode bindings.');
        }

        // Generate the cache key using sprintf
        return md5(sprintf('%s.%s.%s', Str::snake($service), Str::snake($function), $bindingsJson));
    }
}
