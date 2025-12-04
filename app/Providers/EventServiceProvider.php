<?php

namespace App\Providers;

use App\Events\OrderPaid;
use App\Events\PaymentCaptured;
use App\Events\PaymentFailed;
use App\Events\ProductPublished;
use App\Events\StockLevelLow;
use App\Listeners\FinalizeOrderOnPaymentCaptured;
use App\Listeners\InvalidateProductCache;
use App\Listeners\ReleaseStockOnPaymentFailed;
use App\Listeners\SendLowStockAlert;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Payment Events
        PaymentCaptured::class => [
            FinalizeOrderOnPaymentCaptured::class,
        ],
        PaymentFailed::class => [
            ReleaseStockOnPaymentFailed::class,
        ],

        // Inventory Events
        StockLevelLow::class => [
            SendLowStockAlert::class,
        ],

        // Product Events
        ProductPublished::class => [
            InvalidateProductCache::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

