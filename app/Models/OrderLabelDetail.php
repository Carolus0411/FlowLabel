<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLabelDetail extends Model
{
    protected $table = 'order_label_detail';
    protected $guarded = [];

    public function orderLabel(): BelongsTo
    {
        return $this->belongsTo(OrderLabel::class, 'order_label_id', 'id');
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
