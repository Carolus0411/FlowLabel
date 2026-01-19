<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class PurchaseSettlementDetail extends Model
{
    use Filterable;

    protected $table = 'purchase_settlement_detail';
    protected $guarded = [];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }

    public function purchaseSettlement(): BelongsTo
    {
        return $this->belongsTo(PurchaseSettlement::class, 'purchase_settlement_code', 'code')->withDefault();
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_code', 'code')->withDefault();
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_code', 'code')->withDefault();
    }
}
