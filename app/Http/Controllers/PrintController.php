<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\Cast;
use App\Models\CashIn;

class PrintController extends Controller
{
    public function cashIn(CashIn $cashIn)
    {
        $cashIn->load(['details','details.coa']);

        return view('print.cash-in', [
            'cashIn' => $cashIn,
        ]);
    }
}
