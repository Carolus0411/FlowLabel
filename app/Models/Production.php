<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Production extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'productions';

    protected $fillable = [
        'code',
        'date',
        'bom_id',
        'product_id',
        'qty',
        'uom_id',
        'status',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
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

    public function details()
    {
        return $this->hasMany(ProductionDetail::class, 'production_id');
    }

    public static function generateCode()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PRD/{$year}/{$month}/";

        $last = self::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($last) {
            $lastNumber = intval(substr($last->code, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
