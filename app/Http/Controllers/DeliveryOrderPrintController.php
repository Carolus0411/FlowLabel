<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\Company;
use Illuminate\View\View;

class DeliveryOrderPrintController extends Controller
{
    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['contact', 'salesOrder', 'details']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('delivery-order.print', [
            'deliveryOrder' => $deliveryOrder,
            'mainCompany' => $mainCompany,
        ]);
    }
}
