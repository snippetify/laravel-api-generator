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
use Snippetify\SnippetSniffer\WebCrawler;
use Snippetify\SnippetSniffer\SnippetSniffer;
use Illuminate\Contracts\Support\DeferrableProvider;

class ApiGeneratorServiceProvider extends ServiceProvider implements DeferrableProvider
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
        $this->app->bind('apigenerator.crawler', function ($app) {
            $config = $app->make('config');
            return new WebCrawler($config->get('apigenerator', []));
        });

        $this->app->bind('apigenerator.sniffer', function ($app) {
            $config = $app->make('config');
            return new SnippetSniffer($config->get('apigenerator', []));
        });
    }

    /**
     * Register class aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias('apigenerator.crawler', WebCrawler::class);
        $this->app->alias('apigenerator.sniffer', SnippetSniffer::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'apigenerator.crawler',
            'apigenerator.sniffer',
        ];
    }
}
