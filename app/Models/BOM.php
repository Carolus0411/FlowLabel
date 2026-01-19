<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BOM extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'boms';

    protected $fillable = [
        'bom_no',
        'bom_date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'bom_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(BOMDetail::class, 'bom_id');
    }

    public function materials()
    {
        return $this->hasMany(BOMMaterial::class, 'bom_id');
    }

    public static function generateBOMNo()
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "BOM/{$year}/{$month}/";

        $lastBOM = self::where('bom_no', 'like', $prefix . '%')
            ->orderBy('bom_no', 'desc')
            ->first();

        if ($lastBOM) {
            $lastNumber = intval(substr($lastBOM->bom_no, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
