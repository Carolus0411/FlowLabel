<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Filterable;

class ServiceCharge extends Model
{
    use Filterable;

    protected $table = 'service_charge';
    protected $guarded = [];

    public function coaBuying(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'buying_coa_id','id')->withDefault();
    }

    public function coaSelling(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'selling_coa_id','id')->withDefault();
    }
}
