<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Journal extends Model
{
    use Filterable;

    protected $table = 'journal';
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

            if ($model->isDirty('status')) {
                $model->details()->update([
                    'status' => $model->status
                ]);
            }

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

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class,'contact_id','id')->withDefault();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class,'supplier_id','id')->withDefault();
    }

    public function details(): HasMany
    {
        return $this->hasMany(JournalDetail::class,'code','code');
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
		return $this->hasMany(UserLog::class,'ref_id','code')->where('resource', 'Journal');
	}
}
