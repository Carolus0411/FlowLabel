<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Bank extends Model
{
    use Filterable;

    protected $table = 'bank';
    protected $guarded = [];
}
