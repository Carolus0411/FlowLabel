<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\PurchaseOrder;
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
        return PurchaseOrder::query()
            ->with(['supplier'])
            ->whereBetween('order_date', [$this->start_date, $this->end_date])
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->orderBy('order_date')
            ->get();
    }

    public function export()
    {
        $data = $this->getReportData();
        $rows = [];

        foreach ($data as $item) {
            $rows[] = [
                'Date' => $item->order_date,
                'Code' => $item->code,
                'Supplier' => $item->supplier->name ?? '-',
                'Amount' => $item->order_amount,
                'Status' => $item->status,
            ];
        }

        $writer = SimpleExcelWriter::streamDownload('Purchase Order Report.xlsx')
            ->addRows($rows);

        return response()->streamDownload(function () use ($writer) {
            $writer->close();
        }, 'Purchase Order Report.xlsx');
    }
}; ?>

<div>
    <x-header title="Purchase Order Report" separator />

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
                        <th>Supplier</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getReportData() as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->order_date)->format('d/m/Y') }}</td>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->supplier->name ?? '-' }}</td>
                        <td class="text-right">{{ number_format($row->order_amount, 2) }}</td>
                        <td><x-status-badge :status="$row->status" /></td>
                    </tr>
                    @endforeach
                    @if($this->getReportData()->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center">No data available</td>
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
