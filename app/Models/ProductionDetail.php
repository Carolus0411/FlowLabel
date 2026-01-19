<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionDetail extends Model
{
    use HasFactory;

    protected $table = 'production_details';

    protected $fillable = [
        'production_id',
        'material_id',
        'qty',
        'uom_id',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class, 'production_id');
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
