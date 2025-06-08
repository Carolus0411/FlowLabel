<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Cash extends Model
{
    use Filterable;

    protected $table = 'cash';
    protected $guarded = [];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class,'cash_account_id','id')->withDefault();
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'coa_code','code')->withDefault();
    }

    #[Scope]
    protected function in(Builder $query): void
    {
        $query->where('type', 'in');
    }

    #[Scope]
    protected function out(Builder $query): void
    {
        $query->where('type', 'out');
    }
}
