<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class SalesInvoiceDetail extends Model
{
    use Filterable;

    protected $table = 'sales_invoice_detail';
    protected $guarded = [];

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class,'service_charge_id','id')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class,'uom_id','id')->withDefault();
    }
}
