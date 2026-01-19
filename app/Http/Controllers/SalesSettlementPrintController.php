<?php

namespace App\Http\Controllers;

use App\Models\SalesSettlement;
use App\Models\Company;
use Illuminate\View\View;

class SalesSettlementPrintController extends Controller
{
    public function show(SalesSettlement $salesSettlement): View
    {
        $salesSettlement->load(['contact', 'sources', 'details', 'createdBy']);
        $mainCompany = Company::where('type', 'main')->first();

        return view('sales-settlement.print', [
            'salesSettlement' => $salesSettlement,
            'mainCompany' => $mainCompany,
        ]);
    }
}
