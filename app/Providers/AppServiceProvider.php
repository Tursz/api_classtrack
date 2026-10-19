<?php

namespace App\Providers;

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
        if (in_array(strtolower(app()->getLocale()), ['pt_br', 'pt-br', 'pt'])) {
            app()->setLocale('pt_BR');
        }
    }
}
