<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Observers\OrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind interfaces para services (desacoplamento)
        $this->app->bind(
            \App\Contracts\InvoiceServiceInterface::class,
            \App\Services\Invoice\InvoiceService::class
        );

        $this->app->bind(
            \App\Contracts\ShippingServiceInterface::class,
            \App\Services\Shipping\ShippingService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers
        Order::observe(OrderObserver::class);
    }
}
