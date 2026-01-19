<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceivalDetail extends Model
{
    protected $table = 'purchase_receival_detail';
    protected $guarded = [];

    public function purchaseReceival(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceival::class, 'purchase_receival_id', 'id');
    }

    public function purchaseOrderDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderDetail::class, 'purchase_order_detail_id', 'id')->withDefault();
    }

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id', 'id')->withDefault();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id', 'id')->withDefault();
    }
}
