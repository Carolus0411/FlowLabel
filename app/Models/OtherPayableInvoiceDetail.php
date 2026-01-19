<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherPayableInvoiceDetail extends Model
{
    protected $table = 'other_payable_invoice_detail';
    protected $guarded = [];

    public function otherPayableInvoice(): BelongsTo
    {
        return $this->belongsTo(OtherPayableInvoice::class, 'other_payable_invoice_id', 'id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id')->withDefault();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'id')->withDefault();
    }

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id', 'id')->withDefault();
    }

    public function pph(): BelongsTo
    {
        return $this->belongsTo(Pph::class, 'pph_id', 'id')->withDefault();
    }
}
