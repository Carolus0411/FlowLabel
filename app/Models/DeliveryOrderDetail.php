<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderDetail extends Model
{
    protected $table = 'delivery_order_detail';
    protected $guarded = [];

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class, 'delivery_order_id', 'id');
    }

    public function salesOrderDetail(): BelongsTo
    {
        return $this->belongsTo(SalesOrderDetail::class, 'sales_order_detail_id', 'id')->withDefault();
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
