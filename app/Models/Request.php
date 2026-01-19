<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Request extends Model
{
    use Filterable;

    protected $table = 'requests';
    protected $guarded = [];

    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by','id')->withDefault();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class,'updated_by','id')->withDefault();
    }

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->created_by = auth()->id() ?? 0;
        });

        static::updating(function (Model $model) {
            $model->updated_by = auth()->id() ?? 0;
        });
    }
}
