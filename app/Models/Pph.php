<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Pph extends Model
{
    use Filterable;

    protected $table = 'pph';
    protected $guarded = [];
}
