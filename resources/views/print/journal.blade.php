<x-print-layout>

<h1 class="mb-10 text-center font-bold">
    JOURNAL
</h1>

<table class="mb-3 w-full">
<tr>
    <td class="w-1/2 sm:w-2/3 align-top">
        <table class="table">
        <tr>
            <td class="w-[100px]">Type</td>
            <td>:</td>
            <td>{{ $journal->type }}</td>
        </tr>
        <tr>
            <td>Ref. ID</td>
            <td>:</td>
            <td>{{ $journal->ref_id }}</td>
        </tr>
        <tr>
            <td>Ref. Name</td>
            <td>:</td>
            <td>{{ $journal->ref_name }}</td>
        </tr>
        <tr>
            <td>Note</td>
            <td>:</td>
            <td>{{ $journal->note }}</td>
        </tr>
        </table>
    </td>
    <td class="w-1/2 sm:w-1/3 align-top">
        <table class="table">
        <tr>
            <td class="w-[120px]">Trans. No.</td>
            <td>:</td>
            <td class="font-semibold">{{ $journal->code }}</td>
        </tr>
        <tr>
            <td>Trans. Date</td>
            <td>:</td>
            <td>{{ \App\Helpers\Cast::date($journal->date, 'd/m/Y') }}</td>
        </tr>
        </table>
    </td>
</tr>
</table>

<div>
    <table class="table-print text-sm">
    <thead>
    <tr>
        <th class="text-left">Code</th>
        <th class="text-left">Account</th>
        <th class="text-left">Description</th>
        <th class="text-right lg:w-[9rem]">Debit</th>
        <th class="text-right lg:w-[9rem]">Credit</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($journal->details ?? [] as $detail)
    <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
        <td>{{ $detail->coa->code ?? '' }}</td>
        <td>{{ $detail->coa->name ?? '' }}</td>
        <td class="">{{ $detail->description }}</td>
        <td class="text-right">{{ \App\Helpers\Cast::money($detail->debit, 2) }}</td>
        <td class="text-right">{{ \App\Helpers\Cast::money($detail->credit, 2) }}</td>
    </tr>
    @empty
    <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
        <td colspan="5" class="text-center">No record found.</td>
    </tr>
    @endforelse
    </tbody>
    <tfoot>
    <tr class="border-t border-b border-black">
        <td colspan="3" class="text-right font-bold">Total</td>
        <td class="text-right font-bold">{{ \App\Helpers\Cast::money($journal->debit_total) }}</td>
        <td class="text-right font-bold">{{ \App\Helpers\Cast::money($journal->credit_total) }}</td>
    </tr>
    </tfoot>
    </table>
</div>

</x-print-layout>
