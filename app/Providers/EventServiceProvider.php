<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\CashInClosed::class => [
            \App\Listeners\CashInCreateJournal::class,
        ],
        \App\Events\CashInVoided::class => [
            \App\Listeners\CashInVoidJournal::class,
        ],
        \App\Events\CashOutClosed::class => [
            \App\Listeners\CashOutCreateJournal::class,
        ],
        \App\Events\CashOutVoided::class => [
            \App\Listeners\CashOutVoidJournal::class,
        ],
        \App\Events\PurchaseInvoiceClosed::class => [
            \App\Listeners\PurchaseInvoiceCreateJournal::class,
        ],
        \App\Events\PurchaseInvoiceDirectClosed::class => [
            \App\Listeners\PurchaseInvoiceDirectCreateJournal::class,
        ],
        \App\Events\SalesInvoiceClosed::class => [
            \App\Listeners\SalesInvoiceCreateJournal::class,
        ],
        \App\Events\SalesInvoiceDirectClosed::class => [
            \App\Listeners\SalesInvoiceDirectCreateJournal::class,
        ],
        \App\Events\SalesInvoiceVoided::class => [
            \App\Listeners\SalesInvoiceVoidJournal::class,
        ],
        \App\Events\SalesInvoiceDirectVoided::class => [
            \App\Listeners\SalesInvoiceDirectVoidJournal::class,
        ],
        \App\Events\OtherPayableInvoiceClosed::class => [
            \App\Listeners\OtherPayableInvoiceCreateJournal::class,
        ],
        \App\Events\DeliveryOrderClosed::class => [
            \App\Listeners\DeliveryOrderUpdateStock::class,
        ],
        \App\Events\DeliveryOrderVoided::class => [
            \App\Listeners\DeliveryOrderVoidStock::class,
        ],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
