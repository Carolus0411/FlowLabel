<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Intercash;
use App\Models\CashAccount;
use App\Models\BankAccount;
use App\Helpers\Cast;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'intercash_per_page')]
    public int $perPage = 10;

    #[Session(key: 'intercash_date1')]
    public string $date1 = '';

    #[Session(key: 'intercash_date2')]
    public string $date2 = '';

    #[Session(key: 'intercash_code')]
    public $code = '';

    #[Session(key: 'intercash_status')]
    public $status = '';

    public bool $drawer = false;
    public int $filterCount = 0;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view intercash');

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
            ['key' => 'code', 'label' => 'No. IC', 'class' => 'truncate'],
            ['key' => 'date', 'label' => 'Date', 'format' => ['date', 'd/m/Y']],
            ['key' => 'from_account_code', 'label' => 'From Account', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'to_account_code', 'label' => 'To Account', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'amount', 'label' => 'Amount (IDR)', 'class' => 'text-right', 'format' => ['currency', '2.,', '']],
            ['key' => 'type', 'label' => 'Type', 'class' => 'truncate'],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'truncate', 'format' => ['date', 'd/m/y, H:i']],
        ];
    }

    public function create(): void
    {
        $intercash = Intercash::create([
            'code' => uniqid('IC/'),
            'date' => now(),
            'status' => 'open',
        ]);

        $this->redirectRoute('intercash.edit', $intercash->id);
    }

    public function intercashes(): LengthAwarePaginator
    {
        return Intercash::query()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->with(['fromCashAccount', 'toCashAccount', 'fromBankAccount', 'toBankAccount', 'currency', 'approvedBy', 'postedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'intercashes' => $this->intercashes(),
            'statusOptions' => [
                ['id' => 'open', 'name' => 'Open'],
                ['id' => 'approve', 'name' => 'Approved'],
                ['id' => 'post', 'name' => 'Posted'],
                ['id' => 'void', 'name' => 'Voided'],
            ],
        ];
    }

    public function updated($property): void
    {
        if (!is_array($property) && $property != "") {
            $this->resetPage();
            $this->updateFilterCount();
        }
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');
        $this->success('Filters cleared.');
        $this->reset(['code', 'status']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function delete(Intercash $intercash): void
    {
        Gate::authorize('delete intercash');

        if ($intercash->status != 'open') {
            $this->error('Only open intercash can be deleted.');
            return;
        }

        $intercash->delete();
        $this->success('Intercash successfully deleted.');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Intercash" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create intercash')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table
            :headers="$headers"
            :rows="$intercashes"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            show-empty-text
            :link="route('intercash.edit', ['intercash' => '[id]'])"
        >
            @scope('cell_act', $intercash)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('intercash.edit', $intercash->id) }}" icon="o-pencil-square" />
                @if ($intercash->status == 'approve')
                <x-menu-item title="Show Journal" onclick="popupWindow('{{ route('print.journal', ['Intercash', base64_encode($intercash->code)]) }}', 'journal', '1000', '460', 'yes', 'center')" icon="o-magnifying-glass" />
                @endif
            </x-dropdown>
            @endscope
            @scope('cell_status', $intercash)
            <x-status-badge :status="$intercash->status" />
            @endscope
            @scope('cell_from_account_code', $intercash)
            <div class="text-sm">{{ $intercash->from_account_code }}</div>
            @endscope
            @scope('cell_to_account_code', $intercash)
            <div class="text-sm">{{ $intercash->to_account_code }}</div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
        </div>
        <x-input label="No. IC" wire:model="code" />
        <x-select label="Status" wire:model="status" :options="$statusOptions" placeholder="-- All --" />
    </x-search-drawer>
</div>
