<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Filterable;

class Coa extends Model
{
    use Filterable;

    protected $table = 'coa';
    protected $guarded = [];

    public function journalDetails(): HasMany
    {
        return $this->hasMany(JournalDetail::class,'coa_code','code')
        ->where('status', 'close')
        ->orderBy('date','asc');
    }

    protected static function booted(): void
    {
        static::creating(function (Model $model) {
            $model->full_name = $model->code . ' ' . $model->name;
        });

        static::updating(function (Model $model) {
            $model->full_name = $model->code . ' ' . $model->name;
        });
    }

    #[Scope]
    protected function search(Builder $query, string $keyword = ''): void
    {
        $query->where(function (Builder $query) use ($keyword) {
            $query->filterLike('code', $keyword);
            $query->orFilterLike('name', $keyword);
        });
    }
}
