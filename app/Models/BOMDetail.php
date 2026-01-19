<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BOMDetail extends Model
{
    use HasFactory;

    protected $table = 'bom_details';

    protected $fillable = [
        'bom_id',
        'product_id',
        'qty',
        'uom_id',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function bom()
    {
        return $this->belongsTo(BOM::class, 'bom_id');
    }

    public function product()
    {
        return $this->belongsTo(ServiceCharge::class, 'product_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
