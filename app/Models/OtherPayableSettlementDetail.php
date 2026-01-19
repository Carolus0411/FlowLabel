<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;
use App\Models\OtherPayableInvoice;

class OtherPayableSettlementDetail extends Model
{
    use Filterable;

    protected $table = 'other_payable_settlement_detail';
    protected $guarded = [];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }

    public function otherPayableSettlement(): BelongsTo
    {
        return $this->belongsTo(OtherPayableSettlement::class, 'other_payable_settlement_id', 'id');
    }

    public function otherPayableInvoice(): BelongsTo
    {
        return $this->belongsTo(OtherPayableInvoice::class, 'other_payable_invoice_code', 'code')->withDefault();
    }
}
