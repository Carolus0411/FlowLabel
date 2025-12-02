<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class PurchaseSettlement extends Model
{
    use Filterable;

    protected $table = 'purchase_settlement';
    protected $guarded = [];

    #[Scope]
    protected function stored(Builder $query): void
    {
        $query->where('saved', 1);
    }

    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('saved', 0);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(PurchaseSettlementSource::class,'purchase_settlement_code','code');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseSettlementDetail::class,'purchase_settlement_code','code');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class,'supplier_id','id')->withDefault();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by','id')->withDefault();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'updated_by','id')->withDefault();
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'closed_by','id')->withDefault();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class,'ref_id','code')->where('resource', 'PurchaseSettlement');
    }
}
