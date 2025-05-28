<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Currency extends Model
{
    use Filterable;

    protected $table = 'currency';
    protected $guarded = [];
}
