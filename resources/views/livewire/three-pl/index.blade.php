<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\ThreePl;
use Illuminate\Support\Facades\Schema;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'three_pl_per_page')]
    public int $perPage = 10;

    #[Session(key: 'three_pl_name')]
    public string $name = '';

    #[Session(key: 'three_pl_code')]
    public string $code = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view three-pl');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => 'Kode 3PL'],
            ['key' => 'name', 'label' => 'Nama 3PL'],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[100px]'],
        ];
    }

    public function threePls(): LengthAwarePaginator
    {
        // Guard against missing table during early development or before migration
        if (! Schema::hasTable('three_pls')) {
            return new LengthAwarePaginator(collect([]), 0, $this->perPage);
        }

        return ThreePl::query()
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->filterLike('code', $this->code)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'threePls' => $this->threePls(),
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
            'code' => 'nullable',
        ]);
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->name)) $count++;
        if (!empty($this->code)) $count++;
        $this->filterCount = $count;
    }

    public function delete(ThreePl $threePl): void
    {
        Gate::authorize('delete three-pl');
        $threePl->delete();
        $this->success('3PL has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export three-pl');

        if (! Schema::hasTable('three_pls')) {
            $this->error('Database table `three_pls` does not exist. Please run migrations.');
            return null;
        }

        $threePls = ThreePl::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('3PL.xlsx');
        foreach ( $threePls->lazy() as $threePl ) {
            $writer->addRow([
                'id' => $threePl->id ?? '',
                'code' => $threePl->code ?? '',
                'name' => $threePl->name ?? '',
                'is_active' => $threePl->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            return $writer->toBrowser();
        }, '3PL.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="3PL" separator progress-indicator>
        <x-slot:actions>
            @can('export three-pl')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import three-pl')
            <x-button label="Import" link="{{ route('three-pl.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create three-pl')
            <x-button label="Create" link="{{ route('three-pl.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$threePls" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $threePl)
            <x-active-badge :status="$threePl->is_active" />
            @endscope
            @scope('actions', $threePl)
            <div class="flex gap-1.5">
                @can('delete three-pl')
                <x-button wire:click="delete({{ $threePl->id }})" spinner="delete({{ $threePl->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update three-pl')
                <x-button link="{{ route('three-pl.edit', $threePl->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <x-input label="Code" wire:model="code" />
        <x-input label="Name" wire:model="name" />
    </x-search-drawer>
</div>
