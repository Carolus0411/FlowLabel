<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\CashAccount;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'cashaccount_per_page')]
    public int $perPage = 10;

    #[Session(key: 'cashaccount_name')]
    public string $name = '';

    #[Session(key: 'cashaccount_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view cash-account');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'currency.code', 'label' => 'Currency', 'sortable' => false],
            ['key' => 'coa.code', 'label' => 'Coa', 'sortable' => false],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[120px]'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function cashAccounts(): LengthAwarePaginator
    {
        return CashAccount::query()
        ->with(['currency','coa'])
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->active($this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'cashAccounts' => $this->cashAccounts(),
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

    public function delete(CashAccount $cashAccount): void
    {
        Gate::authorize('delete cash-account');
        $cashAccount->delete();
        $this->success('Cash account has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export cash-account');

        $cashAccount = CashAccount::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('CashAccount.xlsx');
        foreach ( $cashAccount->lazy() as $cashAccount ) {
            $writer->addRow([
                'id' => $cashAccount->id ?? '',
                'name' => $cashAccount->name ?? '',
                'currency_id' => $cashAccount->currency_id ?? '',
                'coa_code' => $cashAccount->coa_code ?? '',
                'is_active' => $cashAccount->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'CashAccount.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Cash Account" separator progress-indicator>
        <x-slot:actions>
            @can('export cash-account')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import cash-account')
            <x-button label="Import" link="{{ route('cash-account.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create cash-account')
            <x-button label="Create" link="{{ route('cash-account.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$cashAccounts" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $cashAccount)
            @if ($cashAccount->is_active)
            <x-badge value="Active" class="text-xs uppercase badge-success badge-soft" />
            @else
            <x-badge value="Inactive" class="text-xs uppercase badge-error badge-soft" />
            @endif
            @endscope
            @scope('actions', $cashAccount)
            <div class="flex gap-1.5">
                @can('delete cash-account')
                <x-button wire:click="delete({{ $cashAccount->id }})" spinner="delete({{ $cashAccount->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update cash-account')
                <x-button link="{{ route('cash-account.edit', $cashAccount->id) }}" icon="o-pencil-square" class="btn btn-sm" />
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
