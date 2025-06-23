<?php

use Illuminate\Support\Facades\Gate;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Volt\Component;
use Livewire\Attributes\Session;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use App\Models\UserLog;

new class extends Component {
    use Toast, WithPagination;

    #[Session(key: 'userlog_per_page')]
    public int $perPage = 10;

    #[Session(key: 'userlog_date1')]
    public $date1 = '';

    #[Session(key: 'userlog_date2')]
    public $date2 = '';

    #[Session(key: 'userlog_resource')]
    public string $resource = '';

    #[Session(key: 'userlog_action')]
    public string $action = '';

    #[Session(key: 'userlog_ref_id')]
    public string $ref_id = '';

    public int $filterCount = 0;
    public bool $drawer = false;
    public array $sortBy = ['column' => 'id', 'direction' => 'desc'];

    public function mount(): void
    {
        Gate::authorize('view user-logs');

        if (empty($this->date1)) {
            $this->date1 = date('Y-m-01');
            $this->date2 = date('Y-m-t');
        }

        $this->updateFilterCount();
    }

    public function headers(): array
    {
        return [
            ['key' => 'action', 'label' => 'Action'],
            ['key' => 'resource', 'label' => 'Resource'],
            ['key' => 'ref_id', 'label' => 'Ref. ID'],
            ['key' => 'user.name', 'label' => 'User', 'sortable' => false],
            ['key' => 'created_at', 'label' => 'Created At', 'class' => 'lg:w-[160px]', 'format' => ['date', 'd-M-y, H:i']],
        ];
    }

    public function userLogs(): LengthAwarePaginator
    {
        return UserLog::query()
        ->whereDateBetween('DATE(created_at)', $this->date1, $this->date2)
        ->with('user:id,name')
        ->orderBy(...array_values($this->sortBy))
        ->filterLike('resource', $this->resource)
        ->filterWhere('action', $this->action)
        ->filterWhere('ref_id', $this->ref_id)
        ->paginate($this->perPage);
    }

    public function with(): array
    {
        return [
            'headers' => $this->headers(),
            'userLogs' => $this->userLogs(),
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
            'date1' => 'required|date',
            'date2' => 'required|date',
        ]);
    }

    public function clear(): void
    {
        $this->date1 = date('Y-m-01');
        $this->date2 = date('Y-m-t');

        $this->success('Filters cleared.');
        $this->reset(['resource','action','ref_id']);
        $this->resetPage();
        $this->updateFilterCount();
    }

    public function updateFilterCount(): void
    {
        $count = 0;
        if (!empty($this->resource)) $count++;
        if (!empty($this->action)) $count++;
        if (!empty($this->ref_id)) $count++;
        $this->filterCount = $count;
    }

    public function delete(UserLog $userlog): void
    {
        Gate::authorize('delete user-logs');
        $userlog->delete();
        $this->success('Log has been deleted.');
    }

    public function export()
    {
        Gate::authorize('export user logs');

        $userLogs = UserLog::query()
        ->whereDateBetween('DATE(created_at)', $this->date1, $this->date2)
        ->with(['user:id,name'])
        ->orderBy('id','asc')
        ->get();
        $writer = SimpleExcelWriter::streamDownload('User logs.xlsx');
        foreach ( $userLogs->lazy() as $userLog ) {
            $writer->addRow([
                'id' => $userLog->id ?? '',
                'resource' => $userLog->resource ?? '',
                'action' => $userLog->action ?? '',
                'ref_id' => $userLog->ref_id ?? '',
                'data' => $userLog->data ?? '',
            ]);
        }
        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'User logs.xlsx');
    }
}; ?>

<div>
    {{-- HEADER --}}
    <div class="lg:top-[65px] lg:sticky z-10 bg-base-200 pb-0.5 pt-3">
    <x-header title="User logs" separator progress-indicator>
        <x-slot:subtitle>
            <x-subtitle-date :date1="$date1" :date2="$date2" />
        </x-slot:subtitle>
        <x-slot:actions>
            @can('export user logs')
            <x-button label="Export" wire:click="export" spinner="export" icon="o-arrow-down-tray" />
            @endcan
            <x-button label="Filters" @click="$wire.drawer = true" icon="o-funnel" badge="{{ $filterCount }}" />
        </x-slot:actions>
    </x-header>
    </div>

    {{-- TABLE --}}
    <x-card wire:loading.class="bg-slate-200/50 text-slate-400">
        <x-table
            :headers="$headers"
            :rows="$userLogs"
            :sort-by="$sortBy"
            with-pagination
            per-page="perPage"
            show-empty-text
            :link="route('user-logs.edit', ['userLog' => '[id]'])"
        >
            @scope('cell_action', $userLog)
            @if ($userLog->action == 'update')
            <x-badge value="Update" class="text-xs badge-success" />
            @elseif ($userLog->action == 'delete')
            <x-badge value="Delete" class="text-xs badge-error" />
            @elseif ($userLog->action == 'create')
            <x-badge value="Create" class="text-xs badge-primary" />
            @endif
            @endscope
            @scope('actions', $userLog)
            <div class="flex gap-1.5">
                @can('delete user logs')
                <x-button wire:click="delete({{ $userLog->id }})" spinner="delete({{ $userLog->id }})" wire:confirm="Are you sure you want to delete this row?" icon="o-trash" class="btn btn-sm" />
                @endcan
                @can('update user logs')
                <x-button link="{{ route('user-logs.edit', $userLog->id) }}" icon="o-pencil-square" class="btn btn-sm" />
                @endcan
            </div>
            @endscope
        </x-table>
    </x-card>

    {{-- FILTER DRAWER --}}
    <x-search-drawer>
        <div class="space-y-4 lg:space-y-0 lg:grid grid-cols-2 gap-4">
            <x-datetime label="Start Date" wire:model="date1" />
            <x-datetime label="End Date" wire:model="date2" />
        </div>
        <x-input label="Resource" wire:model="resource" />
        <x-input label="Action" wire:model="action" />
        <x-input label="Ref ID" wire:model="ref_id" />
    </x-search-drawer>
</div>
