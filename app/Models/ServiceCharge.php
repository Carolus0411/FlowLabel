<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Filterable;

class ServiceCharge extends Model
{
    use Filterable;

    protected $table = 'service_charge';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->load('group');
            $groupCode = $model->group?->code ?? '';
            $model->full_name = $model->code . ', ' . $model->name . ($groupCode ? ' [' . $groupCode . ']' : '');
        });

        static::updating(function (Model $model) {
            $model->load('group');
            $groupCode = $model->group?->code ?? '';
            $model->full_name = $model->code . ', ' . $model->name . ($groupCode ? ' [' . $groupCode . ']' : '');
        });
    }

    #[Scope]
    protected function search(Builder $query, mixed $keyword = ''): void
    {
        $query->where(function (Builder $query) use ($keyword) {
            $query->filterLike('code', $keyword);
            $query->orFilterLike('name', $keyword);
        });
    }

    #[Scope]
    protected function export(Builder $query): void
    {
        $query->whereIn('type', ['export','']);
    }

    #[Scope]
    protected function import(Builder $query): void
    {
        $query->whereIn('type', ['import','']);
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    #[Scope]
    protected function air(Builder $query): void
    {
        $query->whereIn('transport', ['air','']);
    }

    #[Scope]
    protected function sea(Builder $query): void
    {
        $query->whereIn('transport', ['sea','']);
    }

    public function coaBuying(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'buying_coa_id','id')->withDefault();
    }

    public function coaSelling(): BelongsTo
    {
        return $this->belongsTo(Coa::class,'selling_coa_id','id')->withDefault();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ServiceChargeGroup::class, 'service_charge_group_id', 'id')->withDefault();
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'product_id');
    }
}
