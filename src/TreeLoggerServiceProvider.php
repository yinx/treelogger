<?php

namespace Yinx\TreeLogger;

use Illuminate\Support\ServiceProvider;

class TreeLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.yinx.treelogger', function ($app) {
            return $app['Yinx\TreeLogger\Commands\TreeLoggerCommand'];
        });
        $this->app->singleton('command.yinx.remove', function ($app) {
            return $app['Yinx\TreeLogger\Commands\RemoveLogsCommand'];
        });
        $this->commands(['command.yinx.treelogger', 'command.yinx.remove']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
    }
}
