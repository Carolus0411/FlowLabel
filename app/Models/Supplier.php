<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use App\Traits\Filterable;

class Supplier extends Model
{
    use Filterable;

    protected $table = 'supplier';
    protected $guarded = [];

    #[Scope]
    protected function isActive(Builder $query): void
    {
        $query->where('is_active', 1);
    }
}
