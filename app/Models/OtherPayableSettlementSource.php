<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherPayableSettlementSource extends Model
{
    protected $table = 'other_payable_settlement_source';
    protected $guarded = [];

    // Typically points to a source like invoice or preload; keep generic for now
}
