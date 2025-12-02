<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class PurchaseSettlementSource extends Model
{
    use Filterable;

    protected $table = 'purchase_settlement_source';
    protected $guarded = [];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }

    public function settleable(): MorphTo
    {
        return $this->morphTo(null, null, null, 'code');
    }

    public function purchaseSettlement(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PurchaseSettlement::class, 'purchase_settlement_code', 'code')->withDefault();
    }
}
