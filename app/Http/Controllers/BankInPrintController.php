<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankIn;
use App\Models\Company;

class BankInPrintController extends Controller
{
    public function show(BankIn $bankIn)
    {
        $mainCompany = Company::first();

        $bankIn->load(['contact', 'bankAccount', 'details.coa', 'details.currency']);

        return view('bank-in.print', [
            'mainCompany' => $mainCompany,
            'bankIn' => $bankIn,
        ]);
    }
}
