<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class OtherPayableSettlement extends Model
{
    use Filterable;

    protected $table = 'other_payable_settlement';
    protected $guarded = [];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class,'bank_account_id','id')->withDefault();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class,'supplier_id','id')->withDefault();
    }

    public function details(): HasMany
    {
        return $this->hasMany(OtherPayableSettlementDetail::class,'other_payable_settlement_id','id');
    }

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

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('status', 'close');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by','id')->withDefault();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'updated_by','id')->withDefault();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class,'ref_id','code')->where('resource', 'OtherPayableSettlement');
    }

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->created_by = auth()->id() ?? 0;
            $model->updated_by = auth()->id() ?? 0;
        });

        static::updating(function (Model $model) {
            $model->updated_by = auth()->id() ?? 0;
        });

        static::updated(function (Model $model) {
            if (auth()->check()) {
                auth()->user()->logs()->create([
                    'resource' => class_basename($model),
                    'action' => $model->isDirty('code') ? 'create' : 'update',
                    'ref_id' => $model->code,
                    'data' => json_encode($model)
                ]);
            }
        });

        static::deleted(function (Model $model) {
            if (auth()->check()) {
                auth()->user()->logs()->create([
                    'resource' => class_basename($model),
                    'action' => 'delete',
                    'ref_id' => $model->code,
                    'data' => json_encode($model)
                ]);
            }
        });
    }
}
