<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReceival;
use App\Models\Company;
use Illuminate\View\View;

class PurchaseReceivalPrintController extends Controller
{
    public function show(PurchaseReceival $purchaseReceival): View
    {
        $purchaseReceival->load(['supplier', 'purchaseOrder', 'details.serviceCharge', 'details.uom']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('purchase-receival.print', [
            'purchaseReceival' => $purchaseReceival,
            'mainCompany' => $mainCompany,
        ]);
    }
}
