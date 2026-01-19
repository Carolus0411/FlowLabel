<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\Company;
use Illuminate\View\View;

class SalesInvoicePrintController extends Controller
{
    public function show(SalesInvoice $salesInvoice): View
    {
        $salesInvoice->load(['contact', 'details.serviceCharge', 'ppn', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('sales-invoice.print', [
            'salesInvoice' => $salesInvoice,
            'mainCompany' => $mainCompany,
        ]);
    }
}
