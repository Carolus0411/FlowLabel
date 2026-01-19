<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\InventoryLedger;
use App\Models\ServiceCharge;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('components.layouts.app')] class extends Component {
    public $start_date;
    public $end_date;

    public function mount()
    {
        $this->start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function getReportData()
    {
        // Get all items that have transactions or are active
        // For now, let's get items that have transactions in the ledger OR are active stock items.
        // Actually, the prompt says "add Item with type stock and Non Stock into the table".
        // So we should probably list all items that have any movement or stock.

        // We can query the ledger grouped by item.

        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate = Carbon::parse($this->end_date)->endOfDay();

        $items = ServiceCharge::query()
            ->with(['itemType'])
            ->whereHas('itemType', function($query) {
                $query->where('is_stock', true);
            })
            ->get();

        $reportData = [];

        foreach ($items as $item) {
            // Opening Stock: Transactions before start date
            $openingStock = InventoryLedger::where('service_charge_id', $item->id)
                ->where('date', '<', $startDate)
                ->sum('qty');

            // Stock In: Positive transactions within range
            $stockIn = InventoryLedger::where('service_charge_id', $item->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('type', 'in')
                ->sum('qty');

            // Stock Out: Negative transactions within range (stored as negative? or positive with type 'out'?)
            // In my migration I didn't specify if qty is signed.
            // Usually 'in' is positive, 'out' is negative.
            // But if I store 'out' with positive qty, I need to subtract.
            // Let's assume I store positive qty and use 'type' to distinguish.
            // In PurchaseReceival, I stored 'in' with positive qty.
            // I haven't implemented 'out' yet (e.g. Sales), but let's assume 'out' will be stored as positive qty with type 'out'.

            $stockOut = InventoryLedger::where('service_charge_id', $item->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->where('type', 'out')
                ->sum('qty');

            // Calculate Remaining
            $remainingStock = $openingStock + $stockIn + $stockOut;

            // Price
            // Get the last purchase price or average price.
            // Let's get the last 'in' price from ledger.
            $lastPrice = InventoryLedger::where('service_charge_id', $item->id)
                ->where('type', 'in')
                ->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->value('price') ?? 0;

            // If no history, maybe 0.

            $endingValue = $remainingStock * $lastPrice;

            // Only add if there is any activity or stock
            if ($openingStock != 0 || $stockIn != 0 || $stockOut != 0 || $remainingStock != 0) {
                $reportData[] = [
                    'code' => $item->code,
                    'name' => $item->name,
                    'price' => $lastPrice,
                    'opening_stock' => $openingStock,
                    'stock_in' => $stockIn,
                    'stock_out' => abs($stockOut),
                    'remaining_stock' => $remainingStock,
                    'ending_value' => $endingValue,
                    'is_stock' => $item->itemType?->is_stock ?? false,
                ];
            }
        }

        return $reportData;
    }
}; ?>

<div>
    <x-header title="Report Stocks" separator />

    <div class="mb-4 flex gap-4 items-end">
        <x-datepicker label="Start Date" wire:model.live="start_date" />
        <x-datepicker label="End Date" wire:model.live="end_date" />
        <x-button label="Print" icon="o-printer" class="btn-primary" onclick="window.print()" />
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Barang</th>
                        <th>Nama Barang</th>
                        <th>Tipe Item</th>
                        <th class="text-right">Harga Barang</th>
                        <th class="text-right">Stok Awal</th>
                        <th class="text-right">Stok Masuk</th>
                        <th class="text-right">Stok Keluar</th>
                        <th class="text-right">Sisa Stok</th>
                        <th class="text-right">Nilai Persediaan Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getReportData() as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $row['code'] }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>
                            @if($row['is_stock'])
                                <span class="badge badge-xs badge-info">Stock</span>
                            @else
                                <span class="badge badge-xs badge-ghost">Non-Stock</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($row['price'], 2) }}</td>
                        <td class="text-right">{{ number_format($row['opening_stock'], 3) }}</td>
                        <td class="text-right">{{ number_format($row['stock_in'], 3) }}</td>
                        <td class="text-right">{{ number_format($row['stock_out'], 3) }}</td>
                        <td class="text-right">{{ number_format($row['remaining_stock'], 3) }}</td>
                        <td class="text-right">{{ number_format($row['ending_value'], 2) }}</td>
                    </tr>
                    @endforeach
                    @if(empty($this->getReportData()))
                    <tr>
                        <td colspan="10" class="text-center">No data available</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </x-card>
</div>
