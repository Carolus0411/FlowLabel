<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class PurchaseInvoiceDirectClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseInvoice;

    public function __construct($purchaseInvoice)
    {
        $this->purchaseInvoice = $purchaseInvoice;
    }
}
