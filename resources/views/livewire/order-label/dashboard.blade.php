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

        // Status breakdown
        $statusBreakdown = (clone $query)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => $item->count]);

        // Recent printed orders (last 10)
        $recentPrinted = OrderLabel::query()
            ->where('saved', 1)
            ->whereNotNull('printed_at')
            ->orderBy('printed_at', 'desc')
            ->limit(10)
            ->get();

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
            'statusBreakdown' => $statusBreakdown,
            'recentPrinted' => $recentPrinted,
            'topBatches' => $topBatches,
            'dailyStats' => $dailyStats,
            'hourlyStats' => $hourlyStats,
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

            {{-- Recent Printed Orders --}}
            <x-card title="Recent Printed Orders" class="shadow-lg">
                <div class="overflow-x-auto">
                    <table class="table table-zebra table-sm">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Batch</th>
                                <th>Page</th>
                                <th>Prints</th>
                                <th>Printed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPrinted as $order)
                            <tr>
                                <td>
                                    <a href="{{ route('order-label.edit', $order->id) }}" class="link link-primary font-medium">
                                        {{ $order->code }}
                                    </a>
                                </td>
                                <td>
                                    @if($order->batch_no)
                                    <x-badge value="{{ $order->batch_no }}" class="badge-info badge-sm font-mono" />
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($order->page_number)
                                    <x-badge value="{{ $order->page_number }}" class="badge-primary badge-sm" />
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td>
                                    <x-badge value="{{ $order->print_count }}x" class="badge-success badge-sm" />
                                </td>
                                <td class="text-sm">{{ $order->printed_at ? \Carbon\Carbon::parse($order->printed_at)->format('d M Y, H:i') : '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-500 py-4">No printed orders yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- Sidebar Section - Takes 1 column --}}
        <div class="space-y-6">
            {{-- Status Breakdown --}}
            <x-card title="Status Breakdown" class="shadow-lg">
                <div class="space-y-3">
                    @forelse($statusBreakdown as $status => $count)
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-base-200 transition-colors">
                        <div class="flex items-center gap-3">
                            @if($status === 'open')
                            <div class="badge badge-info gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                {{ ucfirst($status) }}
                            </div>
                            @elseif($status === 'close')
                            <div class="badge badge-success gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                {{ ucfirst($status) }}
                            </div>
                            @else
                            <div class="badge badge-error gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-4 h-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                {{ ucfirst($status) }}
                            </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl font-bold">{{ number_format($count) }}</span>
                            @if($totalOrders > 0)
                            <span class="text-sm text-gray-500">({{ round(($count / $totalOrders) * 100, 1) }}%)</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-gray-500 py-8">No data available</div>
                    @endforelse
                </div>
            </x-card>

            {{-- Print Efficiency Radial Progress --}}
            <x-card title="Print Efficiency" class="shadow-lg">
                <div class="flex flex-col items-center py-6">
                    <div class="radial-progress text-primary" style="--value:{{ $printEfficiency }}; --size:10rem; --thickness: 12px;" role="progressbar">
                        <span class="text-3xl font-bold">{{ $printEfficiency }}%</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-4">{{ number_format($totalPrinted) }} of {{ number_format($totalOrders) }} printed</p>
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

            // Hourly Chart - Bar chart with DaisyUI colors
            const hourlyCtx = document.getElementById('hourlyChart');
            if (hourlyCtx) {
                @if($period === 'today' && count($hourlyStats) > 0)
                new Chart(hourlyCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(array_column($hourlyStats, 'hour')),
                        datasets: [{
                            label: 'Prints',
                            data: @json(array_column($hourlyStats, 'count')),
                            backgroundColor: 'hsl(var(--p))',
                            borderColor: 'hsl(var(--p))',
                            borderWidth: 0,
                            borderRadius: 8,
                            barThickness: 'flex',
                            maxBarThickness: 30
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 10,
                                cornerRadius: 6,
                                displayColors: false
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
                                        size: 10
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
                @endif
            }
        });
    </script>
</div>
