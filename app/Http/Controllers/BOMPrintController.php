<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BOM;
use App\Models\Company;

class BOMPrintController extends Controller
{
    public function show(BOM $bom)
    {
        $mainCompany = Company::first();

        $bom->load(['details.product', 'details.uom', 'materials.material', 'materials.uom']);

        return view('bom.print', [
            'mainCompany' => $mainCompany,
            'bom' => $bom,
        ]);
    }
}
