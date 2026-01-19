<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\Company;
use Illuminate\View\View;

class SalesOrderPrintController extends Controller
{
    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load(['contact', 'details.serviceCharge', 'ppn', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('sales-order.print', [
            'salesOrder' => $salesOrder,
            'mainCompany' => $mainCompany,
        ]);
    }
}
