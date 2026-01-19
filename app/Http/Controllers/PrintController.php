<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\Cast;
use App\Models\Journal;
use App\Models\CashIn;
use App\Models\BankIn;
use App\Models\BankOut;
use App\Models\OtherPayableSettlement;

class PrintController extends Controller
{
    public function journal(string $resource, string $id)
    {
        if ($resource == 'journal') {

            $journal = Journal::query()
                ->with(['details' => fn($q) => $q->orderBy('debit', 'desc'), 'details.coa', 'supplier', 'contact', 'createdBy'])
                ->where('id', intval($id))
                ->first();

        } else {

            $journal = Journal::query()
                ->with(['details' => fn($q) => $q->orderBy('debit', 'desc'), 'details.coa', 'supplier', 'contact', 'createdBy'])
                ->where('ref_name', $resource)
                ->where('ref_id', base64_decode($id))
                ->first();
        }

        if (!$journal) {
            abort(403, 'JOURNAL IS NOT FOUND');
        }

        return view('print.journal', [
            'journal' => $journal,
        ]);
    }

    public function cashIn(CashIn $cashIn)
    {
        $cashIn->load(['details','details.coa']);

        return view('print.cash-in', [
            'cashIn' => $cashIn,
        ]);
    }

    public function bankIn(BankIn $bankIn)
    {
        $bankIn->load(['details','details.coa']);

        return view('print.bank-in', [
            'bankIn' => $bankIn,
        ]);
    }

    public function bankOut(BankOut $bankOut)
    {
        $bankOut->load(['details','details.coa']);

        return view('print.bank-out', [
            'bankOut' => $bankOut,
        ]);
    }

    public function otherPayableSettlement(OtherPayableSettlement $otherPayableSettlement)
    {
        $otherPayableSettlement->load(['details','details.coa']);

        return view('print.other-payable-settlement', [
            'otherPayableSettlement' => $otherPayableSettlement,
        ]);
    }
}
