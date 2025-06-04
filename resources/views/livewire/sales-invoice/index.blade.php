<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\SalesInvoice;
use App\Models\Journal;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'sales_invoice_per_page')]
    public int $perPage = 10;

    #[Session(key: 'sales_invoice_code')]
    public string $code = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];
    public $journal;
    public $journalModal = false;

    public function mount(): void
    {
        Gate::authorize('view sales invoice');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => '#', 'disableLink' => true, 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'invoice_date', 'label' => 'Invoice Date', 'format' => ['date', 'd-m-Y']],
            ['key' => 'contact.name', 'label' => 'Customer', 'sortable' => false],
            ['key' => 'dpp_amount', 'label' => 'DPP Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'ppn.value', 'label' => 'PPN %', 'class' => 'text-right', 'sortable' => false],
            ['key' => 'ppn_amount', 'label' => 'PPN', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'pph.value', 'label' => 'PPH %', 'class' => 'text-right', 'sortable' => false],
            ['key' => 'pph_amount', 'label' => 'PPH', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'stamp_amount', 'label' => 'Stamp', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'invoice_amount', 'label' => 'Invoice Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            // ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function create(): void
    {
        $salesInvoice = SalesInvoice::create([
            'code' => uniqid(),
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'top' => 30,
            'status' => 'open',
        ]);

        $this->redirectRoute('sales-invoice.edit', $salesInvoice->id);
    }

    public function salesInvoices(): LengthAwarePaginator
    {
        return SalesInvoice::stored()
            ->with(['contact','ppn','pph'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'salesInvoices' => $this->salesInvoices(),
        ];
    }

    public function updated($property): void
    {
        if (! is_array($property) && $property != "") {
            $this->resetPage();
            $this->updateFilterCount();
        }
    }

    public function search(): void
    {
        $data = $this->validate([
            'code' => 'nullable',
        ]);
    }

    public function clear(): void
    {
        $this->success('Filters cleared.');
        $this->reset();
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) {
            $count++;
        }
        $this->filterCount = $count;
    }

    public function delete(SalesInvoice $salesInvoice): void
    {
        Gate::authorize('delete sales invoice');
        $salesInvoice->delete();
        $this->success('Invoice has been deleted.');
    }

    public function showJournal($code): void
    {
        $this->journal = Journal::with(['details.coa'])->where('ref_name', 'SalesInvoice')->where('ref_id', $code)->first();
        $this->journalModal = true;
    }

    public function export()
    {
        Gate::authorize('export sales invoice');

        $salesInvoice = SalesInvoice::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Sales Invoice.xlsx');
        foreach ( $salesInvoice->lazy() as $salesInvoice ) {
            $writer->addRow([
                'id' => $salesInvoice->id ?? '',
                'code' => $salesInvoice->code ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Sales Invoice.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Sales Invoice" separator progress-indicator>
        <x-slot:actions>
            @can('export sales invoice')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import sales invoice')
            <x-button label="Import" link="{{ route('sales-invoice.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create sales invoice')
            {{-- <x-button label="Create" link="{{ route('sales-invoice.create') }}" icon="o-plus" class="btn-primary" /> --}}
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$salesInvoices" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('sales-invoice.edit', ['salesInvoice' => '[id]'])">
            @scope('cell_act', $salesInvoice)
            <x-dropdown class="btn-sm btn-soft">
                <x-menu-item title="Edit" link="{{ route('sales-invoice.edit', $salesInvoice->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Show Journal" wire:click="showJournal('{{ $salesInvoice->code }}')" icon="o-magnifying-glass" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $salesInvoice)
            @if ($salesInvoice->status == 'close')
            <x-badge value="Open" class="text-xs badge-success" />
            @elseif ($salesInvoice->status == 'void')
            <x-badge value="Open" class="text-xs badge-error" />
            @else
            <x-badge value="Open" class="text-xs badge-primary" />
            @endif
            @endscope
            {{-- @scope('actions', $salesInvoice)
            <div class="flex gap-1.5">
                @can('delete sales invoice')
                <x-button wire: click="delete({{ $salesInvoice->id }})" spinner="delete({{ $salesInvoice->id }})" wire: confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update sales invoice')
                <x-button link="{{ route('sales-invoice.edit', $salesInvoice->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope --}}
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <x-input label="Code" wire:model="code" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>

    {{-- JOURNAL MODAL --}}
    <x-modal wire:model="journalModal" title="Journal" persistent box-class="max-w-11/12 lg:max-w-2/3">
        <div class="overflow-x-auto">
            <table class="table">
            <thead>
            <tr>
                <th class="text-left">Account</th>
                <th class="text-left">Description</th>
                <th class="text-right lg:w-[9rem]">Debit</th>
                <th class="text-right lg:w-[9rem]">Credit</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($journal->details ?? [] as $detail)
            <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td><b>{{ $detail->coa->code ?? '' }}</b>, {{ $detail->coa->name ?? '' }}</td>
                <td class="">{{ $detail->description }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($detail->debit, 2) }}</td>
                <td class="text-right">{{ \App\Helpers\Cast::money($detail->credit, 2) }}</td>
            </tr>
            @empty
            <tr class="divide-x divide-gray-200 dark:divide-gray-900 hover:bg-yellow-50 dark:hover:bg-gray-800">
                <td colspan="4" class="text-center">No record found.</td>
            </tr>
            @endforelse
            </tbody>
            </table>
        </div>
        <x-slot:actions>
            <x-button label="Close" icon="o-x-mark" @click="$wire.journalModal = false" />
        </x-slot:actions>
    </x-modal>
</div>
