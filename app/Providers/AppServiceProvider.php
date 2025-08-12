<?php

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
