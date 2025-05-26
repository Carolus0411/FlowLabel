<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Role extends Model
{
    use Filterable;

    protected $table = 'roles';
    protected $guarded = [];
}
