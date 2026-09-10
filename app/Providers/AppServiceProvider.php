<?php

namespace App\Providers;

use App\Http\Middleware\ApiTokenCheck;
use App\Http\Middleware\ChannelTokenCheck;
use App\Services\Channels\ChannelDriverManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ChannelDriverManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::aliasMiddleware('api.token', ApiTokenCheck::class);
        Route::aliasMiddleware('channel.token', ChannelTokenCheck::class);
    }
}
