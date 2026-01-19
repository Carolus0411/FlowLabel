<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashOut;
use App\Models\Company;

class CashOutPrintController extends Controller
{
    public function show(CashOut $cashOut)
    {
        $mainCompany = Company::first();

        $cashOut->load(['contact', 'supplier', 'cashAccount', 'details.coa', 'details.currency']);

        return view('cash-out.print', [
            'mainCompany' => $mainCompany,
            'cashOut' => $cashOut,
        ]);
    }
}
