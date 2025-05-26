<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Permission extends Model
{
    use Filterable;

    protected $table = 'permissions';
    protected $guarded = [];
}
