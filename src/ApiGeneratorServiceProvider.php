<?php
/*
 * This file is part of the snippetify package.
 *
 * (c) Evens Pierre <evenspierre@snippetify.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Snippetify\ApiGenerator;

use Illuminate\Support\ServiceProvider;
use Snippetify\ApiGenerator\Commands\ApiGenerator;
use Illuminate\Contracts\Support\DeferrableProvider;

class ApiGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Get the path of the configuration file shipping with the package.
     *
     * @return string
     */
    public function getConfigPath()
    {
        return dirname(__DIR__) . '/config/apigenerator.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig($this->getConfigPath());
        $this->registerCommands();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerServices();
        $this->registerAliases();
    }

    /**
     * Register a path to be published by the publish command.
     *
     * @param string $path
     * @param string $group
     * @return void
     */
    protected function publishConfig($path, $group = 'config')
    {
        $this->publishes([$path => config_path('apigenerator.php')], $group);
    }

    /**
     * Register the default configuration.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'apigenerator');
    }

    /**
     * Register the Goutte instance.
     *
     * @return void
     */
    protected function registerServices()
    {
        //
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ApiGenerator::class,
            ]);
        }
    }

    /**
     * Register class aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
