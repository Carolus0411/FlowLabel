<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OtherPayableSettlement;
use App\Models\Company;

class OtherPayableSettlementPrintController extends Controller
{
    public function show(OtherPayableSettlement $otherPayableSettlement)
    {
        $mainCompany = Company::first();

        $otherPayableSettlement->load(['supplier', 'details.coa', 'details.currency', 'bankAccount']);

        return view('other-payable-settlement.print', [
            'mainCompany' => $mainCompany,
            'otherPayableSettlement' => $otherPayableSettlement,
        ]);
    }
}
