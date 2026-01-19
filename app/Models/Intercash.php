<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Intercash extends Model
{
    use Filterable;

    protected $table = 'intercash';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->status = empty($model->status) ? 'open' : $model->status;
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

    public function fromCashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'from_cash_account_id', 'id')->withDefault();
    }

    public function toCashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'to_cash_account_id', 'id')->withDefault();
    }

    public function fromBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id', 'id')->withDefault();
    }

    public function toBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id', 'id')->withDefault();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id')->withDefault();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withDefault();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withDefault();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id')->withDefault();
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by', 'id')->withDefault();
    }

    public function cashOut(): BelongsTo
    {
        return $this->belongsTo(CashOut::class, 'cash_out_id', 'id')->withDefault();
    }

    public function bankOut(): BelongsTo
    {
        return $this->belongsTo(BankOut::class, 'bank_out_id', 'id')->withDefault();
    }

    public function cashIn(): BelongsTo
    {
        return $this->belongsTo(CashIn::class, 'cash_in_id', 'id')->withDefault();
    }

    public function bankIn(): BelongsTo
    {
        return $this->belongsTo(BankIn::class, 'bank_in_id', 'id')->withDefault();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class, 'ref_id', 'code')->where('resource', 'Intercash');
    }
}
