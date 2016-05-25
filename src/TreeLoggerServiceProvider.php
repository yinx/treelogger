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
        $this->app['command.yinx.treelogger'] = $this->app->share(
            function ($app) {
                return new Commands\TreeLoggerCommand();
            }
        );
        $this->commands(array('command.yinx.treelogger'));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.yinx.treelogger');
    }
}
