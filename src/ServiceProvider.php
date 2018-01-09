<?php

namespace LowerSpeck;

use Illuminate\Support\ServiceProvider as Base;

class ServiceProvider extends Base
{
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.lower-speck', function () {
            return new Command();
        });
        $this->commands(['command.lower-speck']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.lower-speck'];
    }
}
