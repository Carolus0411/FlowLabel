<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashIn;
use App\Models\Company;

class CashInPrintController extends Controller
{
    public function show(CashIn $cashIn)
    {
        $mainCompany = Company::first();

        $cashIn->load(['contact', 'cashAccount', 'details.coa', 'details.currency']);

        return view('cash-in.print', [
            'mainCompany' => $mainCompany,
            'cashIn' => $cashIn,
        ]);
    }
}
