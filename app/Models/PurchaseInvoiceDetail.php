<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceDetail extends Model
{
    protected $table = 'purchase_invoice_detail';
    protected $guarded = [];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id', 'id');
    }

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id', 'id')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id')->withDefault();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'id')->withDefault();
    }
}
