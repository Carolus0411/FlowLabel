<?php

use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Artisan;

new class extends Component {
    use WithPagination, Toast;

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'total_jobs', 'label' => 'Total Jobs'],
            ['key' => 'pending_jobs', 'label' => 'Pending'],
            ['key' => 'failed_jobs', 'label' => 'Failed'],
            ['key' => 'progress', 'label' => 'Progress'],
            ['key' => 'duration', 'label' => 'Lama Durasi Import'],
            ['key' => 'created_at', 'label' => 'Tanggal dan Jam Import'],
        ];
    }

    public function failedHeaders(): array
    {
        return [
            ['key' => 'id', 'label' => '#'],
            ['key' => 'connection', 'label' => 'Connection'],
            ['key' => 'queue', 'label' => 'Queue'],
            ['key' => 'exception', 'label' => 'Error'],
            ['key' => 'failed_at', 'label' => 'Failed At'],
        ];
    }

    public function with(): array
    {
        return [
            'batches' => DB::table('job_batches')
                ->orderByDesc('created_at')
                ->paginate(10, ['*'], 'batches_page'),
            'failedJobs' => DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->paginate(10, ['*'], 'failed_page'),
            'jobsCount' => DB::table('jobs')->count(),
        ];
    }

    public function retryAllFailed(): void
    {
        Artisan::call('queue:retry', ['id' => 'all']);
        $this->success('Retry command dispatched!');
    }

    public function clearFailed(): void
    {
        Artisan::call('queue:flush');
        $this->success('Failed jobs flushed!');
    }

    public function clearHistory(): void
    {
        DB::table('job_batches')->whereNotNull('finished_at')->delete();
        $this->success('History cleared!');
    }
};
?>

<div>
    <x-header title="Queue Log" separator>
        <x-slot:actions>
             <x-button label="Clear History" icon="o-trash" wire:click="clearHistory" class="btn-sm btn-error" wire:confirm="Are you sure you want to clear completed batches?" />
             <x-button label="Refresh" icon="o-arrow-path" wire:click="$refresh" class="btn-sm" />
             @if($jobsCount > 0)
                <x-badge value="{{ $jobsCount }} Pending Jobs" class="badge-warning" />
             @else
                <x-badge value="No Active Jobs" class="badge-success" />
             @endif
        </x-slot:actions>
    </x-header>

    <div class="mb-5">
        <h3 class="text-lg font-bold mb-3">Import Batches</h3>
        <x-table :headers="$this->headers()" :rows="$batches" with-pagination pagination-key="batches_page">
            @scope('cell_progress', $batch)
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    @php
                        $percentage = $batch->total_jobs > 0 ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100) : 0;
                        $color = 'bg-blue-600';
                        if($batch->failed_jobs > 0) $color = 'bg-red-600';
                        elseif($percentage == 100) $color = 'bg-green-600';
                    @endphp
                    <div class="{{ $color }} h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
                <div class="text-xs text-center mt-1">{{ $percentage }}%</div>
            @endscope
            @scope('cell_duration', $batch)
                @if($batch->finished_at)
                    {{ \Carbon\Carbon::parse($batch->created_at)->diff(\Carbon\Carbon::parse($batch->finished_at))->format('%H:%I:%S') }}
                @else
                    -
                @endif
            @endscope
            @scope('cell_created_at', $batch)
                {{ \Carbon\Carbon::createFromTimestamp($batch->created_at)->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}
            @endscope
        </x-table>
    </div>

    @if($failedJobs->isNotEmpty())
        <div class="mt-8 border-t pt-5">
             <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-bold text-red-600">Failed Jobs</h3>
                <div>
                    <x-button label="Retry All" icon="o-arrow-path" wire:click="retryAllFailed" class="btn-sm btn-warning" />
                    <x-button label="Clear All" icon="o-trash" wire:click="clearFailed" class="btn-sm btn-error" />
                </div>
            </div>

            <x-table :headers="$this->failedHeaders()" :rows="$failedJobs" with-pagination pagination-key="failed_page">
                @scope('cell_exception', $job)
                    <div class="truncate max-w-xs" title="{{ Str::limit($job->exception, 1000) }}">
                        {{ Str::limit($job->exception, 100) }}
                    </div>
                @endscope
                @scope('cell_failed_at', $job)
                     {{ \Carbon\Carbon::parse($job->failed_at)->timezone('Asia/Jakarta')->format('d-m-Y H:i:s') }} ({{ \Carbon\Carbon::parse($job->failed_at)->diffForHumans() }})
                @endscope
            </x-table>
        </div>
    @endif
</div>
