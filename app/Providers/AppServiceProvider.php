<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

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
        // Configuração global de timeout
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        ini_set('max_input_time', 300);
        
        // Log para debug
        Log::info('Timeout configuration:', [
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time')
        ]);
    }
}
