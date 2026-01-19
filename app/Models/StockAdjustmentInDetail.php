<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentInDetail extends Model
{
    protected $table = 'stock_adjustment_in_detail';
    protected $guarded = [];

    public function stockAdjustmentIn(): BelongsTo
    {
        return $this->belongsTo(StockAdjustmentIn::class, 'stock_adjustment_in_id', 'id')->withDefault();
    }

    public function serviceCharge(): BelongsTo
    {
        return $this->belongsTo(ServiceCharge::class, 'service_charge_id', 'id')->withDefault();
    }
}
