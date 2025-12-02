<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Session;
use Illuminate\Support\Collection;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Mary\Traits\Toast;
use App\Models\SalesInvoice;
use App\Models\Contact;
use App\Helpers\Cast;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'aroutstanding_date')]
    public string $date = '';

    #[Session(key: 'aroutstanding_contact_id')]
    public $contact_id = '';

    #[Session(key: 'aroutstanding_code')]
    public $code = '';

    public Collection $contacts;
    public bool $drawer = false;
    public int $filterCount = 0;

    #[Session(key: 'aroutstanding_per_page')]
    public int $perPage = 10;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view sales-invoice');

        if (empty($this->date)) {
            $this->date = date('Y-m-d');
        }

        // Initialize contacts list for the Customer filter
        $this->searchContact('');

        $this->updateFilterCount();
    }

    public function arInvoices(): LengthAwarePaginator
    {
        return $this->arQuery()->paginate($this->perPage);
    }

    public function arQuery()
    {
        $query = SalesInvoice::stored()
            ->whereIn('payment_status', ['unpaid','outstanding'])
            ->when(!empty($this->date), fn($q) => $q->whereDate('invoice_date', '<=', $this->date))
            ->when(!empty($this->code), function ($q) { $q->filterLike('code', $this->code); })
            ->when(!empty($this->contact_id), function ($q) {
                $value = $this->contact_id;
                if (is_array($value)) $value = $value['id'] ?? null;
                if (is_object($value)) $value = $value->id ?? $value;
                if (is_string($value) && is_numeric($value)) $value = intval($value);
                return $q->where('contact_id', $value);
            })
            ->with(['contact','settlementDetails.salesSettlement'])
            ->orderBy('id','asc');

        return $query;
    }

    public function arTotals(): array
    {
        $query = $this->arQuery()->toBase();
        $row = $query->selectRaw('COUNT(*) as total_count, COALESCE(SUM(invoice_amount),0) as sum_invoice_amount, COALESCE(SUM(balance_amount),0) as sum_balance_amount')->first();
        return [
            'total_count' => (int) ($row->total_count ?? 0),
            'sum_invoice_amount' => (float) ($row->sum_invoice_amount ?? 0),
            'sum_balance_amount' => (float) ($row->sum_balance_amount ?? 0),
        ];
    }

    // replaced by arInvoices()

    public function with(): array
    {
        // Recalculate statuses for displayed invoices so page reflects correct statuses
        $invoices = $this->arInvoices();
        foreach ($invoices->items() as $inv) {
            $inv->recalcPaymentStatus();
        }
        // refetch after recalc
        $invoices = $this->arInvoices();
        $totals = $this->arTotals();
        return [
            'headers' => $this->headers(),
            'arInvoices' => $invoices,
            'total_count' => $totals['total_count'],
            'sum_invoice_amount' => $totals['sum_invoice_amount'],
            'sum_balance_amount' => $totals['sum_balance_amount'],
        ];
    }

    public function headers(): array
    {
        return [
            ['key' => 'payment_status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'invoice_date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'contact.name', 'label' => 'Customer', 'sortable' => false, 'class' => 'truncate max-w-[200px]'],
            ['key' => 'invoice_amount', 'label' => 'Invoice Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'balance_amount', 'label' => 'Balance', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'settlement_details', 'label' => 'Settlement Details', 'sortable' => false],
        ];
    }

    public function searchContact(string $value = ''): void
    {
        $selected = Contact::where('id', intval($this->contact_id))->get();
        $this->contacts = Contact::query()
            ->filterLike('name', $value)
            ->isActive()
            ->orderBy('name')
            ->take(20)
            ->get()
            ->merge($selected);
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
            $this->updateFilterCount();
        }
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->contact_id)) $count++;
        if (!empty($this->date)) $count++;
        $this->filterCount = $count;
    }

    public function clear(): void
    {
        $this->date = date('Y-m-d');
        $this->success('Filters cleared.');
        $this->reset(['code','contact_id']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function search(): void
    {
        $data = $this->validate([
            'date' => 'required|date',
            'contact_id' => 'nullable',
            'code' => 'nullable',
        ]);

        // close drawer after apply filters
        $this->drawer = false;
    }

    public function export()
    {
        Gate::authorize('export sales-invoice');

        $query = $this->arQuery()->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('AR Outstanding.xlsx');
        foreach ($query->lazy() as $invoice) {
            $settlements = '';
            if ($invoice->settlementDetails->isNotEmpty()) {
                $settlements = $invoice->settlementDetails->map(function($d){
                    return $d->sales_settlement_code . ' (' . ($d->salesSettlement->date ?? '') . ') : ' . number_format($d->amount ?? 0,2);
                })->implode('; ');
            }

            $writer->addRow([
                'id' => $invoice->id ?? '',
                'code' => $invoice->code ?? '',
                'invoice_date' => $invoice->invoice_date ?? '',
                'customer' => $invoice->contact->name ?? '',
                'invoice_amount' => $invoice->invoice_amount ?? 0,
                'balance_amount' => $invoice->balance_amount ?? 0,
                'payment_status' => $invoice->payment_status ?? '',
                'settlements' => $settlements,
            ]);
        }

        return response()->streamDownload(function() use ($writer) {
            $writer->close();
        }, 'AR Outstanding.xlsx');
    }
}; ?>

<div>
    <x-header title="AR Outstanding" separator>
        <x-slot:subtitle>
            Show unpaid and outstanding customer invoices with applied settlements
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export sales-invoice')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$arInvoices" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_payment_status', $invoice)
            <x-payment-status-badge :data="$invoice" />
            @endscope
            @scope('cell_code', $invoice)
            <div class="truncate max-w-[200px]">{{ $invoice->code }}</div>
            @endscope
            @scope('cell_invoice_date', $invoice)
            <div class="whitespace-nowrap">{{ $invoice->invoice_date }}</div>
            @endscope
            @scope('cell_contact.name', $invoice)
            <div class="whitespace-nowrap">{{ $invoice->contact->name }}</div>
            @endscope
            @scope('cell_invoice_amount', $invoice)
            <div class="text-right">{{ number_format($invoice->invoice_amount,2) }}</div>
            @endscope
            @scope('cell_balance_amount', $invoice)
            <div class="text-right">{{ number_format($invoice->balance_amount,2) }}</div>
            @endscope
            @scope('cell_settlement_details', $invoice)
            @if ($invoice->settlementDetails->isNotEmpty())
            <ul class="list-disc pl-4 text-sm">
                @foreach ($invoice->settlementDetails as $detail)
                    <li>
                        <strong>{{ $detail->sales_settlement_code }}</strong> (
                        {{ $detail->salesSettlement->date ?? '' }}) : {{ number_format($detail->amount ?? 0, 2) }}
                    </li>
                @endforeach
            </ul>
            @else
            <span class="text-slate-400">No Settlement</span>
            @endif
            @endscope
            {{-- Footer totals for the entire filtered set --}}
            <x-slot:footer>
                <tr class="bg-base-200">
                    <td colspan="4" class="text-right font-semibold">Total (Count: {{ $total_count }})</td>
                    <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sum_invoice_amount, 2) }}</td>
                    <td class="text-right font-semibold">{{ \App\Helpers\Cast::money($sum_balance_amount, 2) }}</td>
                    <td></td>
                </tr>
            </x-slot>
        </x-table>
    </x-card>
    {{-- FILTER DRAWER --}}
    <x-search-drawer>
    <x-grid>
        <x-datetime label="Date (Up To)" wire:model="date" />
        <x-choices
            label="Customer"
            wire:model="contact_id"
            :options="$contacts"
            search-function="searchContact"
            option-label="name"
            single
            searchable
            clearable
            placeholder="-- Select --"
        />
        <x-input label="Code" wire:model="code" />
    </x-grid>
    </x-search-drawer>
</div>
