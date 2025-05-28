<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Uom extends Model
{
    use Filterable;

    protected $table = 'uom';
    protected $guarded = [];
}
