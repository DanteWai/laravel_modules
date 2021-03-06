<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModularProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $modules = config('modular.modules');
        $path = config('modular.path');

        if($modules){
            Route::group([
                'prefix' => ''
            ], function () use ($modules, $path) {
                foreach ($modules as $module => $submodules){
                    foreach ($submodules as $sub){
                        $relative_path = "/$module/$sub";

                        Route::middleware('web')
                            ->group(function () use ($module, $sub, $relative_path, $path) {
                                $this->getWebRoutes($module, $sub, $relative_path, $path);
                            });


                        Route::prefix('api')
                            ->middleware('api')
                            ->group(function () use ($module, $sub, $relative_path, $path) {
                                $this->getApiRoutes($module, $sub, $relative_path, $path);
                            });


                    }
                }
            });
        }
    }

    private function getWebRoutes($module, $sub, $relative_path, $path) {
        $routes_path = $path.$relative_path.'/Routes/web.php';

        if(file_exists($routes_path)){
            if($module != config('modular.Public')){
                Route::group([
                    'prefix' => strtolower($module),
                    'middleware' => $this->getMiddleware($module)
                ], function () use ($module, $sub, $routes_path){
                    Route::namespace("App\Modules\\$module\\$sub\Controllers")->group($routes_path);
                });
            } else {
                Route::namespace("App\Modules\\$module\\$sub\Controllers")
                    ->middleware($this->getMiddleware($module))
                    ->group($routes_path);
            }
        }
    }

    private function getApiRoutes($module, $sub, $relative_path, $path) {
        $routes_path = $path.$relative_path.'/Routes/api.php';

        if(file_exists($routes_path)){
            Route::group([
                'prefix' => strtolower($module),
                'middleware' => $this->getMiddleware($module,'api')
            ], function () use ($module, $sub, $routes_path){
                Route::namespace("App\Modules\\$module\\$sub\Controllers")->group($routes_path);
            });
        }
    }

    private function getMiddleware($mod, $type = 'web'): array
    {
        $middleware = [];

        $config =  config('modular.groupMiddleware');

        if(isset($config[$mod])){
            if(array_key_exists($type, $config[$mod])){
                $middleware = array_merge($middleware, $config[$mod][$type]);
            }
        }

        return $middleware;
    }
}
