<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class SalesInvoiceDetail extends Model
{
    use Filterable;

    protected $table = 'sales_invoice_detail';
    protected $guarded = [];
}
