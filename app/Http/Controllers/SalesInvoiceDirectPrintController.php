<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoiceDirect;
use App\Models\Company;
use Illuminate\View\View;

class SalesInvoiceDirectPrintController extends Controller
{
    public function show(SalesInvoiceDirect $salesInvoiceDirect): View
    {
        $salesInvoiceDirect->load(['contact', 'details.serviceCharge', 'ppn', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('sales-invoice-direct.print', [
            'salesInvoiceDirect' => $salesInvoiceDirect,
            'mainCompany' => $mainCompany,
        ]);
    }
}
