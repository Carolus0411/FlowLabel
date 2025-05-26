<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class SalesInvoice extends Model
{
    use Filterable;

    protected $table = 'sales_invoice';
    protected $guarded = [];
}
