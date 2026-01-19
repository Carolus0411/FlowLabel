<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankOut;
use App\Models\Company;

class BankOutPrintController extends Controller
{
    public function show(BankOut $bankOut)
    {
        $mainCompany = Company::first();

        $bankOut->load(['contact', 'supplier', 'bankAccount', 'details.coa', 'details.currency']);

        return view('bank-out.print', [
            'mainCompany' => $mainCompany,
            'bankOut' => $bankOut,
        ]);
    }
}
