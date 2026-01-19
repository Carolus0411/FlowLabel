<?php

namespace App\Http\Controllers;

use App\Models\OrderLabel;
use App\Models\Company;
use Illuminate\View\View;

class OrderLabelPrintController extends Controller
{
    public function show(OrderLabel $orderLabel): View
    {
        $orderLabel->load(['contact', 'details.serviceCharge', 'ppn', 'pph']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('order-label.print', [
            'orderLabel' => $orderLabel,
            'mainCompany' => $mainCompany,
        ]);
    }
}
