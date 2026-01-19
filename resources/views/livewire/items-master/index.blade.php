<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\ServiceCharge;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'servicecharge_per_page')]
    public int $perPage = 10;

    #[Session(key: 'servicecharge_name')]
    public string $name = '';

    #[Session(key: 'servicecharge_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'code', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view service-charge');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'group', 'label' => 'Group'],
            ['key' => 'itemType', 'label' => 'Item Type', 'sortable' => false],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[120px]'],
        ];
    }

    public function serviceCharges(): LengthAwarePaginator
    {
        return ServiceCharge::query()
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->active($this->is_active)
        ->with(['group', 'itemType'])
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'serviceCharges' => $this->serviceCharges(),
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
        if (!empty($this->name)) $count++;
        if (!empty($this->is_active)) $count++;
        $this->filterCount = $count;
    }

    public function delete(ServiceCharge $servicecharge): void
    {
        Gate::authorize('delete service-charge');
        $servicecharge->delete();
        $this->success('Items Master has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export service-charge');

        $serviceCharges = ServiceCharge::with(['coaBuying','coaSelling','group'])->orderBy('id','asc')->get();
        $writer = SimpleExcelWriter::streamDownload('Items Master.xlsx');
        foreach ( $serviceCharges->lazy() as $serviceCharge ) {
            $writer->addRow([
                'id' => $serviceCharge->id ?? '',
                'code' => $serviceCharge->code ?? '',
                'name' => $serviceCharge->name ?? '',
                'type' => $serviceCharge->type ?? '',
                'coa_buying' => $serviceCharge->coaBuying->code,
                'coa_selling' => $serviceCharge->coaSelling->code,
                'group' => $serviceCharge->group?->code,
                'is_active' => $serviceCharge->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Items Master.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0.5 pt-3">
    <x-header title="Items Master" separator progress-indicator>
        <x-slot:actions>
            @can('export service-charge')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import service-charge')
            <x-button label="Import" link="{{ route('items-master.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create service-charge')
            <x-button label="Create" link="{{ route('items-master.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>
    </div>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$serviceCharges" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $serviceCharge)
            <x-active-badge :status="$serviceCharge->is_active" />
            @endscope
            @scope('cell_group', $serviceCharge)
            {{ $serviceCharge->group?->name }}
            @endscope
            @scope('cell_itemType', $serviceCharge)
            {{ $serviceCharge->itemType?->name }}
            @endscope
            @scope('actions', $serviceCharge)
            <div class="flex gap-1.5">
                @can('delete service-charge')
                <x-button wire:click="delete({{ $serviceCharge->id }})" spinner="delete({{ $serviceCharge->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update service-charge')
                <x-button link="{{ route('items-master.edit', $serviceCharge->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <x-input label="Name" wire:model="name" />
        <x-select label="Active" wire:model="is_active" :options="\App\Enums\ActiveStatus::toSelect()" placeholder="-- All --" />
    </x-search-drawer>
</div>

