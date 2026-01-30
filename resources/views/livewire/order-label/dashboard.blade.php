<?php

use Livewire\Volt\Component;
use App\Models\OrderLabel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $period = 'today'; // today, week, month, all

    public function with(): array
    {
        $now = Carbon::now();
        $start = null;
        $end = null;

        // Set date range based on period
        switch($this->period) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case 'month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'all':
            default:
                // No date filter for 'all'
                break;
        }

        // Build base query
        $query = OrderLabel::query()->where('saved', 1);

        if ($start && $end) {
            $query->whereBetween('order_date', [$start, $end]);
        }

        // Total Statistics
        $totalOrders = $query->count();
        $totalPrinted = (clone $query)->whereNotNull('printed_at')->count();
        $totalNotPrinted = (clone $query)->whereNull('printed_at')->count();
        $totalBatches = (clone $query)->whereNotNull('batch_no')->distinct('batch_no')->count('batch_no');

        // Average print count
        $avgPrintCount = (clone $query)->whereNotNull('printed_at')->avg('print_count') ?? 0;

        // Print efficiency (percentage of printed orders)
        $printEfficiency = $totalOrders > 0 ? round(($totalPrinted / $totalOrders) * 100, 2) : 0;

        // Recent printed orders (last 10)
        $recentPrinted = OrderLabel::query()
            ->where('saved', 1)
            ->whereNotNull('printed_at')
            ->orderBy('printed_at', 'desc')
            ->limit(10)
            ->get();

        // Platform breakdown
        $platformStats = OrderLabel::query()
            ->selectRaw('three_pl_id, 
                COUNT(*) as total_orders,
                SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_orders,
                SUM(CASE WHEN printed_at IS NULL THEN 1 ELSE 0 END) as pending_orders')
            ->with('threePl')
            ->where('saved', 1)
            ->when($start && $end, fn($q) => $q->whereBetween('order_date', [$start, $end]))
            ->groupBy('three_pl_id')
            ->orderBy('total_orders', 'desc')
            ->get();

        // Status distribution
        $statusStats = OrderLabel::query()
            ->selectRaw('status, COUNT(*) as count')
            ->where('saved', 1)
            ->when($start && $end, fn($q) => $q->whereBetween('order_date', [$start, $end]))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Recent batches (last 5)
        $recentBatches = OrderLabel::query()
            ->selectRaw('batch_no, 
                three_pl_id,
                COUNT(*) as total_pages,
                SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_count,
                MAX(created_at) as import_date,
                MAX(order_date) as latest_order_date')
            ->with('threePl')
            ->where('saved', 1)
            ->whereNotNull('batch_no')
            ->when($start && $end, fn($q) => $q->whereBetween('order_date', [$start, $end]))
            ->groupBy('batch_no', 'three_pl_id')
            ->orderBy('import_date', 'desc')
            ->limit(5)
            ->get();

        // Print status distribution for pie chart
        $printStatusData = [
            'printed' => $totalPrinted,
            'pending' => $totalNotPrinted,
        ];

        // Top batches by page count
        $topBatches = OrderLabel::query()
            ->select('batch_no', DB::raw('COUNT(*) as total_pages'), DB::raw('SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_count'))
            ->where('saved', 1)
            ->whereNotNull('batch_no')
            ->when($start && $end, fn($q) => $q->whereBetween('order_date', [$start, $end]))
            ->groupBy('batch_no')
            ->orderBy('total_pages', 'desc')
            ->limit(5)
            ->get();

        // Daily statistics for chart (last 7 days)
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $printed = OrderLabel::where('saved', 1)
                ->whereNotNull('printed_at')
                ->whereBetween('printed_at', [$dayStart, $dayEnd])
                ->count();

            $notPrinted = OrderLabel::where('saved', 1)
                ->whereNull('printed_at')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $dailyStats[] = [
                'date' => $date->format('D'),
                'printed' => $printed,
                'not_printed' => $notPrinted,
            ];
        }

        // Hourly statistics for today
        $hourlyStats = [];
        if ($this->period === 'today') {
            for ($h = 0; $h < 24; $h++) {
                $hourStart = $now->copy()->setTime($h, 0, 0);
                $hourEnd = $now->copy()->setTime($h, 59, 59);

                $printed = OrderLabel::where('saved', 1)
                    ->whereNotNull('printed_at')
                    ->whereBetween('printed_at', [$hourStart, $hourEnd])
                    ->count();

                if ($printed > 0) {
                    $hourlyStats[] = [
                        'hour' => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00',
                        'count' => $printed,
                    ];
                }
            }
        }

        return [
            'totalOrders' => $totalOrders,
            'totalPrinted' => $totalPrinted,
            'totalNotPrinted' => $totalNotPrinted,
            'totalBatches' => $totalBatches,
            'avgPrintCount' => round($avgPrintCount, 1),
            'printEfficiency' => $printEfficiency,
            'recentPrinted' => $recentPrinted,
            'topBatches' => $topBatches,
            'dailyStats' => $dailyStats,
            'hourlyStats' => $hourlyStats,
            'platformStats' => $platformStats,
            'statusStats' => $statusStats,
            'recentBatches' => $recentBatches,
            'printStatusData' => $printStatusData,
        ];
    }
}; ?>

<div>
    <x-header title="Order Label Dashboard" separator progress-indicator>
        <x-slot:subtitle>
            Monitor your order label printing activities
        </x-slot:subtitle>
        <x-slot:actions>
            <div class="flex gap-2">
                <x-button label="Today" wire:click="$set('period', 'today')" :class="$period === 'today' ? 'btn-primary btn-sm' : 'btn-outline btn-sm'" />
                <x-button label="Week" wire:click="$set('period', 'week')" :class="$period === 'week' ? 'btn-primary btn-sm' : 'btn-outline btn-sm'" />
                <x-button label="Month" wire:click="$set('period', 'month')" :class="$period === 'month' ? 'btn-primary btn-sm' : 'btn-outline btn-sm'" />
                <x-button label="All" wire:click="$set('period', 'all')" :class="$period === 'all' ? 'btn-primary btn-sm' : 'btn-outline btn-sm'" />
                <div class="divider divider-horizontal mx-1"></div>
                <x-button label="View All Orders" link="{{ route('order-label.index') }}" icon="o-list-bullet" class="btn-primary btn-sm" />
            </div>
        </x-slot:actions>
    </x-header>

    {{-- Statistics Cards in Horizontal Layout --}}
    {{-- <div class="stats stats-vertical lg:stats-horizontal shadow w-full mb-6"> --}}
    <div class="flex items-center gap-8 shadow w-full mb-6">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div class="stat-title">Total Orders</div>
            <div class="stat-value text-primary">{{ number_format($totalOrders) }}</div>
            <div class="stat-desc">{{ ucfirst($period) }} period</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-success">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-title">Printed Orders</div>
            <div class="stat-value text-success">{{ number_format($totalPrinted) }}</div>
            <div class="stat-desc">{{ $printEfficiency }}% efficiency</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="stat-title">Pending Print</div>
            <div class="stat-value text-warning">{{ number_format($totalNotPrinted) }}</div>
            <div class="stat-desc">Awaiting action</div>
        </div>

        <div class="stat">
            <div class="stat-figure text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-8 h-8 stroke-current">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
            <div class="stat-title">Total Batches</div>
            <div class="stat-value text-secondary">{{ number_format($totalBatches) }}</div>
            <div class="stat-desc">Avg {{ $avgPrintCount }}x prints</div>
        </div>
    </div>

    {{-- Main Content in Horizontal Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Charts Section - Takes 2 columns --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- 7 Days Overview Chart --}}
            <x-card title="7 Days Overview" class="shadow-lg">
                <canvas id="dailyChart" height="100"></canvas>
            </x-card>

            {{-- Platform Breakdown --}}
            @if($platformStats->count() > 0)
            <x-card title="Platform Performance" class="shadow-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($platformStats as $platform)
                    <div class="card bg-base-100 border border-base-300">
                        <div class="card-body p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-primary text-primary-content rounded-full w-8">
                                            <span class="text-xs">{{ substr($platform->threePl->name ?? 'N/A', 0, 2) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-sm">{{ $platform->threePl->name ?? 'Unknown Platform' }}</h3>
                                        <p class="text-xs text-gray-500">{{ number_format($platform->total_orders) }} orders</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span>Printed</span>
                                    <span class="font-semibold text-success">{{ $platform->printed_orders }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span>Pending</span>
                                    <span class="font-semibold text-warning">{{ $platform->pending_orders }}</span>
                                </div>
                                @php
                                    $platformEfficiency = $platform->total_orders > 0 ? round(($platform->printed_orders / $platform->total_orders) * 100) : 0;
                                @endphp
                                <progress class="progress progress-primary progress-sm" value="{{ $platformEfficiency }}" max="100"></progress>
                                <p class="text-xs text-center text-gray-500">{{ $platformEfficiency }}% efficiency</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            {{-- Recent Batches --}}
            <x-card title="Recent Batches" class="shadow-lg">
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>Batch No</th>
                                <th>Platform</th>
                                <th>Pages</th>
                                <th>Printed</th>
                                <th>Progress</th>
                                <th>Import Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $batch)
                            <tr>
                                <td>
                                    <x-badge value="{{ $batch->batch_no }}" class="badge-info badge-sm font-mono" />
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder">
                                            <div class="bg-secondary text-secondary-content rounded-full w-6">
                                                <span class="text-xs">{{ substr($batch->threePl->name ?? 'N/A', 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <span class="text-sm">{{ $batch->threePl->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <x-badge value="{{ $batch->total_pages }}" class="badge-neutral badge-sm" />
                                </td>
                                <td>
                                    <x-badge value="{{ $batch->printed_count }}" class="badge-success badge-sm" />
                                </td>
                                <td>
                                    @php
                                        $progress = $batch->total_pages > 0 ? round(($batch->printed_count / $batch->total_pages) * 100) : 0;
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <progress class="progress progress-success progress-xs flex-1" value="{{ $progress }}" max="100"></progress>
                                        <span class="text-xs font-semibold">{{ $progress }}%</span>
                                    </div>
                                </td>
                                <td class="text-sm">{{ $batch->import_date ? \Carbon\Carbon::parse($batch->import_date)->format('d M Y') : '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-4">No recent batches</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- Sidebar Section - Takes 1 column --}}
        <div class="space-y-6">
            {{-- Print Efficiency Radial Progress --}}
            <x-card title="Print Efficiency" class="shadow-lg">
                <div class="flex flex-col items-center py-6">
                    <div class="radial-progress text-primary" style="--value:{{ $printEfficiency }}; --size:10rem; --thickness: 12px;" role="progressbar">
                        <span class="text-3xl font-bold">{{ $printEfficiency }}%</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-4">{{ number_format($totalPrinted) }} of {{ number_format($totalOrders) }} printed</p>
                </div>
            </x-card>

            {{-- Print Status Distribution --}}
            <x-card title="Print Status" class="shadow-lg">
                <canvas id="statusChart" height="200"></canvas>
            </x-card>

            {{-- Status Distribution --}}
            @if($statusStats->count() > 0)
            <x-card title="Order Status" class="shadow-lg">
                <div class="space-y-3">
                    @foreach($statusStats as $status => $count)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full {{ $status === 'open' ? 'bg-warning' : 'bg-success' }}"></div>
                            <span class="text-sm capitalize">{{ $status }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold">{{ number_format($count) }}</span>
                            <span class="text-xs text-gray-500">({{ $totalOrders > 0 ? round(($count / $totalOrders) * 100) : 0 }}%)</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-card>
            @endif

            {{-- Quick Actions --}}
            <x-card title="Quick Actions" class="shadow-lg">
                <div class="space-y-2">
                    <x-button label="Import PDF" link="{{ route('order-label.import') }}" icon="o-cloud-arrow-up" class="btn-primary btn-sm w-full justify-start" />
                    <x-button label="View All Orders" link="{{ route('order-label.index') }}" icon="o-list-bullet" class="btn-outline btn-sm w-full justify-start" />
                    <x-button label="Export Data" link="{{ route('order-label.index') }}?export=1" icon="o-arrow-down-tray" class="btn-outline btn-sm w-full justify-start" />
                </div>
            </x-card>

            {{-- Hourly Activity (Today only) --}}
            @if($period === 'today' && count($hourlyStats) > 0)
            <x-card title="Today's Activity" class="shadow-lg">
                <canvas id="hourlyChart" height="200"></canvas>
            </x-card>
            @endif
        </div>
    </div>

    {{-- Top Batches --}}
    <x-card title="Top Batches by Pages" class="shadow-lg mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            @forelse($topBatches as $batch)
            <div class="card bg-base-100 border border-base-300 hover:shadow-xl transition-all">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-2">
                        <x-badge value="{{ $batch->batch_no }}" class="badge-primary badge-sm font-mono" />
                        <span class="text-2xl font-bold">{{ $batch->total_pages }}</span>
                    </div>
                    <div class="text-xs space-y-1 mb-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Printed:</span>
                            <span class="font-semibold text-success">{{ $batch->printed_count }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pending:</span>
                            <span class="font-semibold text-warning">{{ $batch->total_pages - $batch->printed_count }}</span>
                        </div>
                    </div>
                    @php
                        $percentage = $batch->total_pages > 0 ? round(($batch->printed_count / $batch->total_pages) * 100) : 0;
                    @endphp
                    <progress class="progress progress-success" value="{{ $percentage }}" max="100"></progress>
                    <p class="text-xs text-center text-gray-500 mt-1">{{ $percentage }}% complete</p>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center text-gray-500 py-8">No batches available</div>
            @endforelse
        </div>
    </x-card>

    {{-- Chart.js Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Daily Chart - Line chart with DaisyUI colors
            const dailyCtx = document.getElementById('dailyChart');
            if (dailyCtx) {
                new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: @json(array_column($dailyStats, 'date')),
                        datasets: [
                            {
                                label: 'Printed',
                                data: @json(array_column($dailyStats, 'printed')),
                                borderColor: 'hsl(var(--su))',
                                backgroundColor: 'hsla(var(--su), 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                pointBackgroundColor: 'hsl(var(--su))',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            },
                            {
                                label: 'Not Printed',
                                data: @json(array_column($dailyStats, 'not_printed')),
                                borderColor: 'hsl(var(--wa))',
                                backgroundColor: 'hsla(var(--wa), 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                pointBackgroundColor: 'hsl(var(--wa))',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 15,
                                    font: {
                                        size: 12,
                                        weight: '500'
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: {
                                    size: 13,
                                    weight: '600'
                                },
                                bodyFont: {
                                    size: 12
                                },
                                displayColors: true,
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // Status Chart - Doughnut chart
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Printed', 'Pending'],
                        datasets: [{
                            data: [@json($printStatusData['printed']), @json($printStatusData['pending'])],
                            backgroundColor: [
                                'hsl(var(--su))',
                                'hsl(var(--wa))'
                            ],
                            borderColor: [
                                'hsl(var(--su))',
                                'hsl(var(--wa))'
                            ],
                            borderWidth: 2,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 10,
                                cornerRadius: 6,
                                callbacks: {
                                    label: function(context) {
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        },
                        cutout: '60%'
                    }
                });
            }
        });
    </script>
</div>
