<?php

namespace Niogu\Lardgets;

use Illuminate\Routing\Router;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;

class LardgetsServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->loadViewsFrom(__DIR__.'/views', 'lardgets');

        $router->post('__widget2', '\Niogu\Lardgets\LardgetController@run')->middleware('web');
        $router->get('__widgets.js', function() {
            return file_get_contents(__DIR__ . '/js/dist/app.bundle.js');
        })->middleware('web');

        \Route::macro('lardget', function($route, $name) use ($router) {
            $router->get($route, function() use ($name) {
                return app(\Niogu\Lardgets\LardgetController::class)->lardgetRoute($name);
            })->middleware('web');
        });

        \Blade::directive('lardgetsjs', function () {
            return '{{ \Niogu\Lardgets\LardgetsServiceProvider::scriptTag() }}';
        });

        $this->commands([
            MakeLardgetCommand::class,
        ]);
    }

    public static function scriptTag()
    {
        $version = filemtime(__DIR__ . '/js/dist/app.bundle.js');
        return new HtmlString("<script src=\"/__widgets.js?$version\"></script>");
    }
}
