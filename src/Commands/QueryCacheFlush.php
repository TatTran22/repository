<?php

namespace Darkness\Repository\Commands;

use Illuminate\Console\Command;

class QueryCacheFlush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:cache-flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all query cache';

    public function handle()
    {
        \Darkness\Repository\Cache\FlushCache::all();
    }
}
