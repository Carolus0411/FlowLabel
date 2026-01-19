<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Illuminate\Support\Facades\Schema;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Company;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'company_per_page')]
    public int $perPage = 10;

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view company');
    }

    public function headers(): array
    {
        return [
            ['key' => 'act', 'label' => '#', 'disableLink' => true, 'sortable' => false],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Company Name'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'email', 'label' => 'Email', 'sortable' => false, 'class' => 'max-w-[200px] truncate'],
            ['key' => 'is_active', 'label' => 'Active'],
        ];
    }

    public function create(): void
    {
        $this->redirectRoute('company.create');
    }

    public function companies(): LengthAwarePaginator
    {
        if (! Schema::hasTable('companies')) {
            return new LengthAwarePaginator([], 0, $this->perPage);
        }

        return Company::orderBy(...array_values($this->sortBy))
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'companies' => $this->companies(),
        ];
    }
}; ?>

<div>
    <x-header title="Company Master" separator progress-indicator>
        <x-slot:actions>
            @can('create company')
            <x-button label="Create" wire:click="create" spinner="create" icon="o-plus" class="btn-primary" />
            @endcan
        </x-slot:actions>
    </x-header>

    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table :headers="$headers" :rows="$companies" :sort-by="$sortBy" with-pagination per-page="perPage" show-empty-text :link="route('company.edit', ['company' => '[id]'])">
            @scope('cell_act', $company)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('company.edit', $company->id) }}" icon="o-pencil-square" />
                <x-menu-item title="Delete" wire:click="delete({{ $company->id }})" icon="o-trash" />
            </x-dropdown>
            @endscope
            @scope('cell_type', $company)
            <span class="badge {{ $company->type == 'main' ? 'badge-primary' : 'badge-secondary' }}">
                {{ $company->type == 'main' ? 'Main Office' : 'Branch' }}
            </span>
            @endscope
            @scope('cell_is_active', $company)
            <span class="badge {{ $company->is_active ? 'badge-success' : 'badge-error' }}">
                {{ $company->is_active ? 'Active' : 'Inactive' }}
            </span>
            @endscope
        </x-table>
    </x-card>
</div>
