<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\BankAccount;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'bankaccount_per_page')]
    public int $perPage = 10;

    #[Session(key: 'bankaccount_name')]
    public string $name = '';

    #[Session(key: 'bankaccount_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];
    public array $activeList;

    public function mount(): void
    {
        Gate::authorize('view bank-account');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'bank.name', 'label' => 'Bank', 'sortable' => false],
            ['key' => 'currency.code', 'label' => 'Currency', 'sortable' => false],
            ['key' => 'coa.code', 'label' => 'Coa', 'sortable' => false],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[120px]'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function bankAccounts(): LengthAwarePaginator
    {
        return BankAccount::query()
        ->with(['bank','currency','coa'])
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->active($this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'bankAccounts' => $this->bankAccounts(),
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
            'name' => 'nullable',
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
        if (!empty($this->name)) {
            $count++;
        }
        if (!empty($this->is_active)) {
            $count++;
        }
        $this->filterCount = $count;
    }

    public function delete(BankAccount $bankAccount): void
    {
        Gate::authorize('delete bank-account');
        $bankAccount->delete();
        $this->success('Bank account has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export bank-account');

        $bankAccount = BankAccount::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('BankAccount.xlsx');
        foreach ( $bankAccount->lazy() as $bankAccount ) {
            $writer->addRow([
                'id' => $bankAccount->id ?? '',
                'name' => $bankAccount->name ?? '',
                'bank_id' => $bankAccount->bank_id ?? '',
                'currency_id' => $bankAccount->currency_id ?? '',
                'coa_code' => $bankAccount->coa_code ?? '',
                'is_active' => $bankAccount->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'BankAccount.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Bank Account" separator progress-indicator>
        <x-slot:actions>
            @can('export bank-account')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import bank-account')
            <x-button label="Import" link="{{ route('bank-account.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create bank-account')
            <x-button label="Create" link="{{ route('bank-account.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$bankAccounts" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $bankAccount)
            @if ($bankAccount->is_active)
            <x-badge value="Active" class="text-xs uppercase badge-success badge-soft" />
            @else
            <x-badge value="Inactive" class="text-xs uppercase badge-error badge-soft" />
            @endif
            @endscope
            @scope('actions', $bankAccount)
            <div class="flex gap-1.5">
                @can('delete bank-account')
                <x-button wire:click="delete({{ $bankAccount->id }})" spinner="delete({{ $bankAccount->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update bank-account')
                <x-button link="{{ route('bank-account.edit', $bankAccount->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-form wire:submit="search">
            <div class="grid gap-4">
                <x-input label="Name" wire:model="name" />
                <x-select label="Active" wire:model="is_active" :options="\App\Enums\ActiveStatus::toSelect()" placeholder="-- All --" />
            </div>
            <x-slot:actions>
                <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner="clear" />
                <x-button label="Search" icon="o-magnifying-glass" spinner="search" type="submit" class="btn-primary" />
            </x-slot:actions>
        </x-form>
    </x-drawer>
</div>
