<?php

namespace TatTran\Repository;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Intervention\Image\ImageServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register()
    {
        $this->registerImageServiceProvider();
        $this->registerTagRequestMiddleware();
    }

    /**
     * Register the Intervention Image service provider.
     *
     * @return void
     */
    protected function registerImageServiceProvider()
    {
        $this->app->register(ImageServiceProvider::class);
    }

    /**
     * Register the 'tag-request' middleware.
     *
     * @return void
     * @throws BindingResolutionException
     */
    protected function registerTagRequestMiddleware()
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('tag-request', Middleware\TagForRequestMiddleware::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerQueryCacheFlushCommand();
    }

    /**
     * Register the query cache flush command if running in console.
     *
     * @return void
     */
    protected function registerQueryCacheFlushCommand()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\QueryCacheFlush::class
            ]);
        }
    }
}
