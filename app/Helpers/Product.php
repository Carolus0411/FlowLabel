<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use App\Models\Product as ProductModel;

class Product {

    public static function featured( int $limit = 5 )
    {
        return ProductModel::active()->featured()->latest()->get();
    }
}
