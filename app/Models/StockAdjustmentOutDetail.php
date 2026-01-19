<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentOutDetail extends Model
{
    protected $table = 'stock_adjustment_out_detail';
    protected $guarded = [];

    public function stockAdjustmentOut(): BelongsTo
    {
        return $this->belongsTo(StockAdjustmentOut::class, 'stock_adjustment_out_id', 'id')->withDefault();
    }

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id', 'id')->withDefault();
    }
}
