<?php

use App\Helpers\Cast;
?>

<x-print>
    <x-print-header title="Bank Out" />

    <div class="print-content">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <b>Code:</b> {{ $bankOut->code }}<br />
                <b>Date:</b> {{ $bankOut->date }}<br />
            </div>
            <div class="text-right">
                <b>Account:</b> {{ $bankOut->bankAccount->name ?? '' }}<br />
                <b>Contact:</b> {{ $bankOut->contact->name ?? '' }}<br />
            </div>
        </div>

        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Coa</th>
                    <th>Description</th>
                    <th class="text-right">Currency</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">FG Amount</th>
                    <th class="text-right">IDR Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bankOut->details as $detail)
                <tr>
                    <td>{{ $detail->coa->code ?? '' }}</td>
                    <td>{{ $detail->note ?? '' }}</td>
                    <td class="text-right">{{ $detail->currency->code ?? '' }}</td>
                    <td class="text-right">{{ Cast::money($detail->currency_rate, 2) }}</td>
                    <td class="text-right">{{ Cast::money($detail->foreign_amount, 2) }}</td>
                    <td class="text-right">{{ Cast::money($detail->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">Total</td>
                    <td class="text-right">{{ Cast::money($bankOut->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-print>
