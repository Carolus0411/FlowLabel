<?php

namespace App\Http\Controllers;

use App\Models\PurchaseSettlement;
use App\Models\Company;
use Illuminate\View\View;

class PurchaseSettlementPrintController extends Controller
{
    public function show(PurchaseSettlement $purchaseSettlement): View
    {
        $purchaseSettlement->load(['supplier', 'sources', 'details', 'createdBy']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('purchase-settlement.print', [
            'purchaseSettlement' => $purchaseSettlement,
            'mainCompany' => $mainCompany,
        ]);
    }
}
