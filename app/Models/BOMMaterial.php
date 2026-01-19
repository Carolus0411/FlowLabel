<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BOMMaterial extends Model
{
    use HasFactory;

    protected $table = 'bom_materials';

    protected $fillable = [
        'bom_id',
        'material_id',
        'required_qty',
        'uom_id',
        'available_qty',
        'is_sufficient',
    ];

    protected $casts = [
        'required_qty' => 'decimal:2',
        'available_qty' => 'decimal:2',
        'is_sufficient' => 'boolean',
    ];

    public function bom()
    {
        return $this->belongsTo(BOM::class, 'bom_id');
    }

    public function material()
    {
        return $this->belongsTo(ServiceCharge::class, 'material_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }
}
