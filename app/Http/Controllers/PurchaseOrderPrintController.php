<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Company;
use Illuminate\View\View;

class PurchaseOrderPrintController extends Controller
{
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'details.serviceCharge', 'ppn', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('purchase-order.print', [
            'purchaseOrder' => $purchaseOrder,
            'mainCompany' => $mainCompany,
        ]);
    }
}
