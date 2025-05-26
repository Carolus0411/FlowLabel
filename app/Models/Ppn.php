<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Ppn extends Model
{
    use Filterable;

    protected $table = 'ppn';
    protected $guarded = [];
}
