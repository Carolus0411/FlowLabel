<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\PurchaseReceival;
use Carbon\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;

new #[Layout('components.layouts.app')] class extends Component {
    public $start_date;
    public $end_date;
    public $status = '';

    public function mount()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getReportData()
    {
        return PurchaseReceival::query()
            ->with(['supplier', 'purchaseOrder'])
            ->whereBetween('receival_date', [$this->start_date, $this->end_date])
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy('receival_date')
            ->get();
    }

    public function export()
    {
        $data = $this->getReportData();
        $rows = [];

        foreach ($data as $item) {
            $rows[] = [
                'Date' => $item->receival_date,
                'Code' => $item->code,
                'PO Code' => $item->purchaseOrder->code ?? '-',
                'Supplier' => $item->supplier->name ?? '-',
                'Total Qty' => $item->total_qty,
                'Status' => $item->status,
            ];
        }

        $writer = SimpleExcelWriter::streamDownload('Purchase Receival Report.xlsx')
            ->addRows($rows);

        return response()->streamDownload(function () use ($writer) {
            $writer->close();
        }, 'Purchase Receival Report.xlsx');
    }
}; ?>

<div>
    <x-header title="Purchase Receival Report" separator />

    <div class="mb-4 flex gap-4 items-end no-print">
        <x-datepicker label="Start Date" wire:model.live="start_date" />
        <x-datepicker label="End Date" wire:model.live="end_date" />
        <x-select label="Status" wire:model.live="status" :options="[['id' => '', 'name' => 'All'], ['id' => 'open', 'name' => 'Open'], ['id' => 'close', 'name' => 'Closed'], ['id' => 'void', 'name' => 'Void']]" />
        <x-button label="Export Excel" icon="o-arrow-down-tray" wire:click="export" class="btn-success" />
        <x-button label="Print" icon="o-printer" class="btn-primary" onclick="window.print()" />
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Code</th>
                        <th>PO Code</th>
                        <th>Supplier</th>
                        <th class="text-right">Total Qty</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getReportData() as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->receival_date)->format('d/m/Y') }}</td>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->purchaseOrder->code ?? '-' }}</td>
                        <td>{{ $row->supplier->name ?? '-' }}</td>
                        <td class="text-right">{{ number_format($row->total_qty, 2) }}</td>
                        <td><x-status-badge :status="$row->status" /></td>
                    </tr>
                    @endforeach
                    @if($this->getReportData()->isEmpty())
                    <tr>
                        <td colspan="6" class="text-center">No data available</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</div>
