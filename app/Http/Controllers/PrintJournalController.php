<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Helpers\Cast;
use App\Models\Journal;

class ViewCoaController extends Controller
{
    public function index(string $resource, string $id)
    {

        if ($resource == 'sales-invoice')
        {
            $journal = Journal::where('ref_name', 'SalesInvoice')->first();
        }

        return view('print.view-coa', [
            'journal' => $journal,
        ]);
    }
}
