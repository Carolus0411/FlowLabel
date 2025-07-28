<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class CashIn extends Model
{
    use Filterable;

    protected $table = 'cash_in';
    protected $guarded = [];

    public function settlements(): MorphMany
    {
        return $this->morphMany(SalesSettlementSource::class, 'settleable', null, null, 'code');
    }

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
		return $this->hasMany(CashInDetail::class,'cash_in_id','id');
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
		return $this->hasMany(UserLog::class,'ref_id','code')->where('resource', 'CashIn');
	}

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->created_by = auth()->user()->id;
            $model->updated_by = auth()->user()->id;
        });

        static::updating(function (Model $model) {
            $model->updated_by = auth()->user()->id;
        });

        static::updated(function (Model $model) {
            auth()->user()->logs()->create([
                'resource' => class_basename($model),
                'action' => $model->isDirty('code') ? 'create' : 'update',
                'ref_id' => $model->code,
                'data' => json_encode($model)
            ]);
        });

        static::deleted(function (Model $model) {
            auth()->user()->logs()->create([
                'resource' => class_basename($model),
                'action' => 'delete',
                'ref_id' => $model->code,
                'data' => json_encode($model)
            ]);
        });
    }
}
