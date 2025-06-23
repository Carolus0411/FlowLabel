<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Uom;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'uom_per_page')]
    public int $perPage = 10;

    #[Session(key: 'uom_name')]
    public string $name = '';

    #[Session(key: 'uom_active')]
    public string $is_active = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'code', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view uom');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[120px]'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function currencies(): LengthAwarePaginator
    {
        return Uom::query()
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->active($this->is_active)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'currencies' => $this->currencies(),
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

    public function delete(Uom $uom): void
    {
        Gate::authorize('delete uom');
        $uom->delete();
        $this->success('Uom has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export uom');

        $uom = Uom::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Uom.xlsx');
        foreach ( $uom->lazy() as $uom ) {
            $writer->addRow([
                'id' => $uom->id ?? '',
                'code' => $uom->code ?? '',
                'name' => $uom->name ?? '',
                'is_active' => $uom->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Uom.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Uom" separator progress-indicator>
        <x-slot:actions>
            @can('export uom')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import uom')
            <x-button label="Import" link="{{ route('uom.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create uom')
            <x-button label="Create" link="{{ route('uom.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$currencies" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $uom)
            <x-active-badge :status="$uom->is_active" />
            @endscope
            @scope('actions', $uom)
            <div class="flex gap-1.5">
                @can('delete uom')
                <x-button wire:click="delete({{ $uom->id }})" spinner="delete({{ $uom->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update uom')
                <x-button link="{{ route('uom.edit', $uom->id) }}" icon="o-pencil-square" class="btn btn-sm" />
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
