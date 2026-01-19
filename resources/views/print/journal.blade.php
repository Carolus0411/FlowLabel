<x-print-layout>

<style>
    .info-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        margin-bottom: 20px;
        font-size: 12px;
    }

    .info-left, .info-right {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .info-right {
        text-align: right;
    }

    .info-row {
        display: flex;
        gap: 10px;
    }

    .info-label {
        min-width: 120px;
    }

    .journal-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 11px;
    }

    .journal-table th, .journal-table td {
        border: 1px solid black;
        padding: 8px;
        text-align: left;
    }

    .journal-table th {
        background-color: white;
        font-weight: bold;
        text-align: center;
    }

    .center {
        text-align: center;
    }

    .right {
        text-align: right;
    }

    .total-row {
        font-weight: bold;
    }

    .signature-section {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        margin-top: 40px;
        font-size: 12px;
        text-align: center;
    }

    .signature-box {
        padding: 10px;
    }

    .signature-line {
        margin-top: 60px;
        border-bottom: 1px dotted black;
        display: inline-block;
        width: 150px;
    }

    .footer {
        margin-top: 10px;
        font-size: 10px;
    }
</style>

<h1 class="mb-5 text-center font-bold">
    JURNAL
</h1>

<div class="info-section">
    <div class="info-left">
        <div class="info-row">
            <span class="info-label">Tipe</span>
            <span>: {{ ucfirst($journal->type) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">No. Reff</span>
            <span>: {{ $journal->ref_id }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Nama Reff</span>
            <span>: {{ $journal->supplier->name ?? $journal->contact->name ?? $journal->ref_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Keterangan</span>
            <span>: {{ $journal->note }}</span>
        </div>
    </div>
    <div class="info-right">
        <div class="info-row" style="justify-content: flex-end;">
            <span class="info-label">No. Transaksi</span>
            <span>: <strong>{{ $journal->code }}</strong></span>
        </div>
        <div class="info-row" style="justify-content: flex-end;">
            <span class="info-label">Tanggal Transaksi</span>
            <span>: {{ \App\Helpers\Cast::date($journal->date, 'd-M-Y') }}</span>
        </div>
    </div>
</div>

<table class="journal-table">
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama Perkiraan</th>
            <th>CC</th>
            <th>Debet</th>
            <th>Kredit</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($journal->details ?? [] as $detail)
        <tr>
            <td>{{ $detail->coa->code ?? '' }}</td>
            <td>
                {{ $detail->coa->name ?? '' }}<br>
                <small>{{ $detail->description }}</small>
            </td>
            <td class="center">GEN</td>
            <td class="right">{{ \App\Helpers\Cast::money($detail->debit, 2) }}</td>
            <td class="right">{{ \App\Helpers\Cast::money($detail->credit, 2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="5" class="center">No record found.</td>
        </tr>
        @endforelse
        <tr class="total-row">
            <td colspan="3" class="right">Total</td>
            <td class="right">{{ \App\Helpers\Cast::money($journal->debit_total, 2) }}</td>
            <td class="right">{{ \App\Helpers\Cast::money($journal->credit_total, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="signature-section">
    <div class="signature-box">
        <div><strong>Diposting Oleh</strong></div>
        <div class="signature-line"></div>
    </div>
    <div class="signature-box">
        <div><strong>Diperiksa Oleh</strong></div>
        <div class="signature-line"></div>
    </div>
    <div class="signature-box">
        <div><strong>Dibuat Oleh</strong></div>
        <div class="signature-line"></div>
        <div style="margin-top: 5px;">{{ $journal->createdBy->name ?? '' }}</div>
    </div>
</div>

<div class="footer">
    Printed by: {{ auth()->user()->name ?? ($journal->createdBy->name ?? 'System') }} | Printed at: {{ now()->format('Y-m-d H:i:s') }}
</div>

</x-print-layout>
