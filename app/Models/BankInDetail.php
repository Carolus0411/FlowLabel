<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class BankInDetail extends Model
{
    use Filterable;

    protected $table = 'bank_in_detail';
    protected $guarded = [];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }
}
