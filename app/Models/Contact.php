<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class Contact extends Model
{
    use Filterable;

    protected $table = 'contact';
    protected $guarded = [];
}
