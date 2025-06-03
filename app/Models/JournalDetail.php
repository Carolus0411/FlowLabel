<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Traits\Filterable;

class JournalDetail extends Model
{
    use Filterable;

    protected $table = 'journal_detail';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->year = Carbon::parse($model->date)->format('Y');
            $model->month = Carbon::parse($model->date)->format('Ym');
            $model->amount = $model->dc == 'D' ? $model->debit : ($model->credit * -1);
        });

        static::updating(function (Model $model) {
            $model->year = Carbon::parse($model->date)->format('Y');
            $model->month = Carbon::parse($model->date)->format('Ym');
            $model->amount = $model->dc == 'D' ? $model->debit : ($model->credit * -1);
        });
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class,'code','code')->withDefault();
    }

    public function scopeJoinJournal($query)
    {
        return $query
        ->where('journal.status', 'close')
        ->leftJoin('journal','journal.code','=','journal_detail.code');
    }
}
