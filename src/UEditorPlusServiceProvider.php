<?php

/*
 * This file is part of the sanlilin/laravel-ueditor-plus.
 *
 * (c) sanlilin <wanghongbin816@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sanlilin\LaravelUEditorPlus;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

/**
 * Class UEditorPlusServiceProvider.
 */
class UEditorPlusServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @param Router $router
     */
    public function boot(Router $router)
    {
        $this->loadViewsFrom(__DIR__.'/views', 'ueditor-plus');
        $this->loadTranslationsFrom(__DIR__.'/translations', 'ueditor-plus');

        $this->publishes([
            __DIR__.'/config/ueditor-plus.php' => config_path('ueditor-plus.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/assets/ueditor-plus' => public_path('vendor/ueditor-plus'),
        ], 'assets');

        $this->publishes([
            __DIR__.'/views' => base_path('resources/views/vendor/ueditor-plus'),
            __DIR__.'/translations' => base_path('resources/lang/vendor/ueditor-plus'),
        ], 'resources');

        $this->registerRoute($router);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/ueditor-plus.php', 'ueditor-plus');
        $this->app->singleton('ueditor-plus.storage', function ($app) {
            return new StorageManager(Storage::disk($app['config']->get('ueditor-plus.disk', 'public')));
        });
    }

    /**
     * Register routes.
     *
     * @param $router
     */
    protected function registerRoute($router)
    {
        if (!$this->app->routesAreCached()) {
            $router->group(['namespace' => __NAMESPACE__], function ($router) {
                $router->any(config('ueditor-plus.server_url', '/ueditor-plus/server'), 'UEditorPlusController@serve');
            });
        }
    }
}
