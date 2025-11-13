<?php

namespace App\Providers;

use App\Contracts\ERPInterface;
use App\Services\ERP\BlingV3Adapter;
use Illuminate\Support\ServiceProvider;

class ERPServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind da interface ERP para implementação Bling v3
        // Para trocar de ERP, basta mudar esta linha
        $this->app->singleton(ERPInterface::class, BlingV3Adapter::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
