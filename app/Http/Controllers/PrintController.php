<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\Cast;
use App\Models\Journal;
use App\Models\CashIn;

class PrintController extends Controller
{
    public function journal(string $resource, string $id)
    {
        $journal = Journal::query()
            ->with('details.coa')
            ->where('ref_name', $resource)
            ->where('ref_id', base64_decode($id))
            ->first();

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
}
