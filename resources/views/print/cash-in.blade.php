<x-print-layout>

<h1 class="mb-10 text-center font-bold">
    BUKTI PENERIMAAN
</h1>

<div>
    <table class="table-print text-sm">
    <thead>
    <tr>
        <th>No. Perk.</th>
        <th>Nama Perkiraan</th>
        <th>CC</th>
        <th>Nominal</th>
        <th>Kurs</th>
        <th>Total (IDR)</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($cashIn->details as $detail)
    <tr class="last:border-b border-black">
        <td class="text-left align-top">{{ $detail->coa->code }}</td>
        <td>
            {{ $detail->coa->name }}<br />
            {{ $detail->note }}
        </td>
        <td class="align-top"></td>
        <td class="text-right align-top">{{ \App\Helpers\Cast::money($detail->foreign_amount) }}</td>
        <td class="text-right align-top">{{ \App\Helpers\Cast::money($detail->currency_rate) }}</td>
        <td class="text-right align-top">{{ \App\Helpers\Cast::money($detail->amount) }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="border-b border-black">
        <td colspan="5" class="text-right font-bold">Total</td>
        <td class="text-right font-bold">{{ \App\Helpers\Cast::money($cashIn->total_amount) }}</td>
    </tr>
    </tfoot>
    </table>
</div>

</x-print-layout>
