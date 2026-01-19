<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\Company;
use Illuminate\View\View;

class PurchaseInvoicePrintController extends Controller
{
    public function show(PurchaseInvoice $purchaseInvoice): View
    {
        $purchaseInvoice->load(['supplier', 'details.serviceCharge', 'details.uom', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('purchase-invoice.print', [
            'purchaseInvoice' => $purchaseInvoice,
            'mainCompany' => $mainCompany,
        ]);
    }
}
