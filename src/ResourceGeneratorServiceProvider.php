<?php

namespace LaravelResource\ResourceMaker;

use Illuminate\Support\ServiceProvider;

class ResourceGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.laravelresource.makeresource', function ($app) {
            return $app['LaravelResource\ResourceMaker\Commands\ResourceMakeCommand'];
        });
        $this->commands('command.laravelresource.makeresource');

    }

}