<?php

namespace App\Http\Controllers;

use App\Models\OtherPayableInvoice;
use App\Models\Company;
use Illuminate\View\View;

class OtherPayableInvoicePrintController extends Controller
{
    public function show(OtherPayableInvoice $otherPayableInvoice): View
    {
        $otherPayableInvoice->load(['supplier', 'details.serviceCharge', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('other-payable-invoice.print', [
            'otherPayableInvoice' => $otherPayableInvoice,
            'mainCompany' => $mainCompany,
        ]);
    }
}
