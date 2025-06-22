<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Balance extends Model
{
    use Filterable;

    protected $table = 'balance';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->amount = $model->dc == 'D' ? $model->debit : ($model->credit * -1);
        });

        static::updating(function (Model $model) {
            $model->amount = $model->dc == 'D' ? $model->debit : ($model->credit * -1);
        });
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }
}
