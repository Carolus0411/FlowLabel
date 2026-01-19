<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Production;
use App\Models\Company;

class ProductionPrintController extends Controller
{
    public function show(Production $production)
    {
        $mainCompany = Company::first();

        $production->load(['bom', 'product', 'uom', 'details.material', 'details.uom']);

        return view('production.print', [
            'mainCompany' => $mainCompany,
            'production' => $production,
        ]);
    }
}
