<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Supplier;
use Illuminate\Support\Facades\Schema;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'supplier_per_page')]
    public int $perPage = 10;

    #[Session(key: 'supplier_name')]
    public string $name = '';

    #[Session(key: 'supplier_code')]
    public string $code = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    public function mount(): void
    {
        Gate::authorize('view supplier');
        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'code', 'label' => 'Supplier Code'],
            ['key' => 'name', 'label' => 'Supplier Name'],
            ['key' => 'contact_name', 'label' => 'Contact Name', 'class' => 'lg:w-[160px]'],
            ['key' => 'address_1', 'label' => 'Address 1', 'class' => 'lg:w-[160px]'],
            ['key' => 'address_2', 'label' => 'Address 2', 'class' => 'lg:w-[160px]'],
            ['key' => 'telephone', 'label' => 'Telephone', 'class' => 'lg:w-[120px]'],
            ['key' => 'mobile_phone', 'label' => 'Mobile Phone', 'class' => 'lg:w-[120px]'],
            ['key' => 'email', 'label' => 'E-mail', 'class' => 'lg:w-[160px]'],
            ['key' => 'npwp', 'label' => 'No. NPWP', 'class' => 'lg:w-[160px]'],
            ['key' => 'information', 'label' => 'Information', 'class' => 'lg:w-[280px]'],
            ['key' => 'term_of_payment', 'label' => 'Term Of Payment', 'class' => 'lg:w-[120px]'],
            ['key' => 'is_active', 'label' => 'Active', 'class' => 'lg:w-[100px]'],
        ];
    }

    public function suppliers(): LengthAwarePaginator
    {
        // Guard against missing table during early development or before migration
        if (! Schema::hasTable('supplier')) {
            return new LengthAwarePaginator(collect([]), 0, $this->perPage);
        }

        return Supplier::query()
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('name', $this->name)
        ->filterLike('code', $this->code)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'suppliers' => $this->suppliers(),
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

    public function delete(Supplier $supplier): void
    {
        Gate::authorize('delete supplier');
        $supplier->delete();
        $this->success('Supplier has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export supplier');

        if (! Schema::hasTable('supplier')) {
            $this->error('Database table `supplier` does not exist. Please run migrations.');
            return null;
        }

        $supplier = Supplier::orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Supplier.xlsx');
        foreach ( $supplier->lazy() as $supplier ) {
            $writer->addRow([
                'id' => $supplier->id ?? '',
                'code' => $supplier->code ?? '',
                'name' => $supplier->name ?? '',
                'contact_name' => $supplier->contact_name ?? '',
                'address_1' => $supplier->address_1 ?? '',
                'address_2' => $supplier->address_2 ?? '',
                'telephone' => $supplier->telephone ?? '',
                'mobile_phone' => $supplier->mobile_phone ?? '',
                'email' => $supplier->email ?? '',
                'npwp' => $supplier->npwp ?? '',
                'information' => $supplier->information ?? '',
                'term_of_payment' => $supplier->term_of_payment ?? '',
                'is_active' => $supplier->is_active ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Supplier.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <x-header title="Supplier" separator progress-indicator>
        <x-slot:actions>
            @can('export supplier')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            @can('import supplier')
            <x-button label="Import" link="{{ route('supplier.import') }}" icon="o-arrow-up-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
            @can('create supplier')
            <x-button label="Create" link="{{ route('supplier.create') }}" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$suppliers" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text>
            @scope('cell_is_active', $supplier)
            <x-active-badge :status="$supplier->is_active" />
            @endscope
            @scope('actions', $supplier)
            <div class="flex gap-1.5">
                @can('delete supplier')
                <x-button wire:click="delete({{ $supplier->id }})" spinner="delete({{ $supplier->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update supplier')
                <x-button link="{{ route('supplier.edit', $supplier->id) }}" icon="o-pencil-square" class="btn btn-sm" />
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
