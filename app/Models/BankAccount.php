<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class BankAccount extends Model
{
    use Filterable;

    protected $table = 'bank_account';
    protected $guarded = [];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class,'bank_id','id')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id','id')->withDefault();
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }
}
