<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Filterable;

class ServiceChargeGroup extends Model
{
    use Filterable;

    protected $table = 'service_charge_group';
    protected $guarded = [];

    #[Scope]
    protected function search(Builder $query, mixed $keyword = ''): void
    {
        $query->where(function (Builder $query) use ($keyword) {
            $query->filterLike('code', $keyword);
            $query->orFilterLike('name', $keyword);
        });
    }
}
