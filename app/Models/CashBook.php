<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class CashBook extends Model
{
    use Filterable;

    protected $table = 'cash_book';
    protected $guarded = [];

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class,'cash_account_id','id')->withDefault();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class,'contact_id','id')->withDefault();
    }

    public function details(): HasMany
	{
		return $this->hasMany(CashBookDetail::class,'cash_book_id','id');
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
    protected function in(Builder $query): void
    {
        $query->where('type', 'in');
    }

    #[Scope]
    protected function out(Builder $query): void
    {
        $query->where('type', 'out');
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
		return $this->hasMany(UserLog::class,'ref_id','code')->where('resource', 'CashBook');
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
