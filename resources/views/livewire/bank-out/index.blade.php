<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\BankOut;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'bankout_per_page')]
    public int $perPage = 10;

    #[Session(key: 'bankout_date1')]
    public string $date1 = '';

    #[Session(key: 'bankout_date2')]
    public string $date2 = '';

    #[Session(key: 'bankout_code')]
    public $code = '';

    #[Session(key: 'bankout_status')]
    public $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view bank-out');

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => '#', 'disableLink' => true, 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'code', 'label' => 'Code', 'class' => 'truncate'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'supplier.name', 'label' => 'Supplier', 'sortable' => false, 'class' => 'max-w-[300px] truncate'],
            ['key' => 'total_amount', 'label' => 'Total Amount', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'note', 'label' => 'Note', 'class' => 'max-w-[300px] truncate'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'truncate', 'format' => ['date', 'd/m/y, H:i']],
        ];
    }

    public function create(): void
    {
        $bankOut = BankOut::create([
            'code' => uniqid(),
            'date' => Carbon::now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('bank-out.edit', $bankOut->id);
    }

    public function bankOuts(): LengthAwarePaginator
    {
        return BankOut::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->with(['supplier'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'bankOuts' => $this->bankOuts(),
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
            'date1' => 'required',
            'date2' => 'required',
        ]);
    }

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');

        $this->success('Filters cleared.');
        $this->reset(['code','status']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }

    public function export()
    {
        Gate::authorize('export bank-out');

        $bankOut = BankOut::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('BankOut.xlsx');
        foreach ( $bankOut->lazy() as $bankOut ) {
            $writer->addRow([
                'id' => $bankOut->id,
                'code' => $bankOut->code,
                'date' => $bankOut->date,
                'note' => $bankOut->note,
                'bank_account_id' => $bankOut->bank_account_id,
                'supplier_id' => $bankOut->supplier_id,
                'total_amount' => $bankOut->total_amount,
                'status' => $bankOut->status,
                'saved' => $bankOut->saved,
                'created_by' => $bankOut->created_by,
                'updated_by' => $bankOut->updated_by,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'BankOut.xlsx');
    }
}; ?>
<div>
    {{-- HEADER --}}
    <x-header title="Bank Out" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export bank-out')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" responsive />
            @endcan
            @can('import bank-out')
            <x-button label="Import" link="{{ route('bank-out.import') }}" icon="o-arrow-up-tray" class="btn-soft" responsive />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create bank-out')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table
            :headers="$headers"
            :rows="$bankOuts"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            show-empty-text
            :link="route('bank-out.edit', ['bankOut' => '[id]'])"
        >
            @scope('cell_act', $bankOut)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('bank-out.edit', $bankOut->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Show Journal" onclick="popupWindow('{{ route('print.journal', ['BankOut', base64_encode($bankOut->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" icon="o-magnifying-glass" />
                <x-menu-item title="Print" onclick="popupWindow('{{ route('print.bank-out', $bankOut->id) }}', 'bank-out', '1000', '560', 'yes', 'center')" icon="o-printer" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $bankOut)
            <x-status-badge :status="$bankOut->status" />
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
        </div>
        <x-input label="Code" wire:model="code" />
        <x-select label="Status" wire:model="status" :options="\App\Enums\Status::toSelect()" placeholder="-- All --" />
    </x-search-drawer>
</div>
