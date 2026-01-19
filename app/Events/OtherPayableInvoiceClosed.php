<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class OtherPayableInvoiceClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $otherPayableInvoice;

    public function __construct($otherPayableInvoice)
    {
        $this->otherPayableInvoice = $otherPayableInvoice;
    }
}

