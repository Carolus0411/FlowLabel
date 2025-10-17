<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Livewire\Attributes\Session;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\Request;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'request_per_page')]
    public int $perPage = 10;

    #[Session(key: 'request_date1')]
    public $date1 = '';

    #[Session(key: 'request_date2')]
    public $date2 = '';

    #[Session(key: 'request_code')]
    public $code = '';

    #[Session(key: 'request_type')]
    public $type = '';

    #[Session(key: 'request_status')]
    public $status = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view request');

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
            ['key' => 'requestable.code', 'label' => 'Ref ID', 'class' => 'truncate'],
            ['key' => 'requestable_type', 'label' => 'Ref Type', 'class' => 'truncate'],
            ['key' => 'description', 'label' => 'Description', 'class' => 'max-w-[300px] truncate'],
            ['key' => 'response', 'label' => 'Response', 'class' => 'max-w-[300px] truncate'],
            ['key' => 'createdBy.name', 'label' => 'Created By'],
            ['key' => 'updatedBy.name', 'label' => 'Updated By'],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'truncate', 'format' => ['date', 'd-m-y, H:i']],
            ['key' => 'updated_at', 'label' => 'Updated At', 'class' => 'truncate', 'format' => ['date', 'd-m-y, H:i']],
        ];
    }

    public function requests(): LengthAwarePaginator
    {
        return Request::query()
            ->whereDateBetween('DATE(created_at)', $this->date1, $this->date2)
            ->with(['requestable','createdBy','updatedBy'])
            ->orderBy(...array_values($this->sortBy))
            ->filterLike('code', $this->code)
            ->filterWhere('type', $this->type)
            ->filterWhere('status', $this->status)
            ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'requests' => $this->requests(),
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
        $this->reset(['code','type','status']);
        $this->resetPage();
        $this->updateFilterCount();
        $this->drawer = false;
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->code)) $count++;
        if (!empty($this->type)) $count++;
        if (!empty($this->status)) $count++;
        $this->filterCount = $count;
    }

    public function export()
    {
        Gate::authorize('export request');

        $request = request::stored()
            ->whereDateBetween('DATE(date)', $this->date1, $this->date2)
            ->orderBy('id','asc');
        $writer = SimpleExcelWriter::streamDownload('Request.xlsx');
        foreach ( $request->lazy() as $request ) {
            $writer->addRow([
                'id' => $request->id,
                'code' => $request->code,
                'type' => $request->type,
                'requestable_id' => $request->requestable_id,
                'requestable_type' => $request->requestable_type,
                'description' => $request->description,
                'response' => $request->response,
                'status' => $request->status,
                'created_by' => $request->created_by,
                'updated_by' => $request->updated_by,
                'created_at' => $request->created_at,
                'updated_at' => $request->updated_at,
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Request.xlsx');
    }
}; ?>
<div>
    {{-- HEADER --}}
    <x-header title="Request" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export request')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" class="btn-soft" responsive />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" class="btn-soft" responsive />
            @can('create request')
            <x-button label="Create" link="{{ route('request.create') }}" icon="o-plus" class="btn-primary" responsive />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table
            :headers="$headers"
            :rows="$requests"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            show-empty-text
            :link="route('request.edit', ['request' => '[id]'])"
        >
            @scope('cell_act', $request)
            <x-dropdown class="btn-xs btn-soft">
                <x-menu-item title="Edit" link="{{ route('request.edit', $request->id) }}" icon="o-pencil-square" />
            </x-dropdown>
            @endscope
            @scope('cell_status', $request)
            <x-request-badge :status="$request->status" />
            @endscope
            @scope('cell_requestable_type', $request)
            {{ class_basename($request->requestable_type) }}
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
        <x-select label="Type" wire:model="type" :options="\App\Enums\RequestType::toSelect()" placeholder="-- All --" />
        <x-select label="Status" wire:model="status" :options="\App\Enums\RequestStatus::toSelect()" placeholder="-- All --" />
    </x-search-drawer>
</div>
