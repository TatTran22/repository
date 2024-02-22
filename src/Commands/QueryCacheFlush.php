<?php

namespace TatTran\Repository\Commands;

use Illuminate\Console\Command;
use TatTran\Repository\Cache\FlushCache;

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        FlushCache::all();
    }
}
