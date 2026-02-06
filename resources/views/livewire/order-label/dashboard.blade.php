<?php

use Livewire\Volt\Component;
use App\Models\OrderLabel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $period = 'today'; // today, week, month, all
    public array $platformChartData = [];
    public array $platformPieData = [];
    private string $timezone = 'Asia/Jakarta'; // UTC+7

    public function with(): array
    {
        // Set Carbon timezone ke UTC+7
        Carbon::setLocale('id');
        $now = Carbon::now($this->timezone);
        $start = null;
        $end = null;

        // Set date range based on period dengan timezone UTC+7
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

        // Build base query - Perhatikan konversi timezone untuk field tanggal
        $baseQuery = OrderLabel::query()
            ->where('saved', 1)
            ->with('threePl')
            ->when($start && $end, function($q) use ($start, $end) {
                // Konversi waktu ke UTC untuk query database
                $utcStart = $start->copy()->timezone('UTC');
                $utcEnd = $end->copy()->timezone('UTC');
                return $q->whereBetween('order_date', [$utcStart, $utcEnd]);
            });

        // Total Statistics
        $totalOrders = (clone $baseQuery)->count();
        $totalPrinted = (clone $baseQuery)->whereNotNull('printed_at')->count();
        $totalNotPrinted = $totalOrders - $totalPrinted;
        $totalBatches = (clone $baseQuery)->whereNotNull('batch_no')->distinct('batch_no')->count('batch_no');

        // Additional useful statistics dengan timezone UTC+7
        $todayStart = $now->copy()->startOfDay()->timezone('UTC');
        $todayEnd = $now->copy()->endOfDay()->timezone('UTC');

        $todayPrinted = OrderLabel::where('saved', 1)
            ->whereNotNull('printed_at')
            ->whereBetween('printed_at', [$todayStart, $todayEnd])
            ->count();

        $todayPending = OrderLabel::where('saved', 1)
            ->whereNull('printed_at')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // Average print count
        $avgPrintCount = (clone $baseQuery)->whereNotNull('printed_at')->avg('print_count') ?? 0;

        // Print efficiency
        $printEfficiency = $totalOrders > 0 ? round(($totalPrinted / $totalOrders) * 100, 2) : 0;

        // Platform statistics dengan konversi timezone
        $platformStats = OrderLabel::query()
            ->select([
                'three_pl_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_orders'),
                DB::raw('SUM(CASE WHEN printed_at IS NULL THEN 1 ELSE 0 END) as pending_orders')
            ])
            ->with('threePl')
            ->where('saved', 1)
            ->when($start && $end, function($q) use ($start, $end) {
                $utcStart = $start->copy()->timezone('UTC');
                $utcEnd = $end->copy()->timezone('UTC');
                return $q->whereBetween('order_date', [$utcStart, $utcEnd]);
            })
            ->groupBy('three_pl_id')
            ->orderByDesc('total_orders')
            ->limit(6)
            ->get();

        // Prepare chart data for platform comparison
        $this->platformChartData = [
            'type' => 'bar',
            'data' => [
                'labels' => $platformStats->map(fn($p) => $p->threePl->name ?? 'Unknown')->toArray(),
                'datasets' => [
                    [
                        'label' => 'Total Label',
                        'data' => $platformStats->pluck('total_orders')->toArray(),
                        'backgroundColor' => [
                            'rgba(59, 130, 246, 0.8)',  // blue
                            'rgba(16, 185, 129, 0.8)',  // green
                            'rgba(245, 158, 11, 0.8)',  // amber
                            'rgba(139, 92, 246, 0.8)',  // purple
                            'rgba(236, 72, 153, 0.8)',  // pink
                            'rgba(239, 68, 68, 0.8)',   // red
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'indexAxis' => 'y',
                'scales' => [
                    'x' => [
                        'beginAtZero' => true,
                        'ticks' => [
                            'stepSize' => 1
                        ]
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ]
                ]
            ]
        ];

        // Prepare pie chart data for platform distribution
        $this->platformPieData = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $platformStats->map(fn($p) => $p->threePl->name ?? 'Unknown')->toArray(),
                'datasets' => [
                    [
                        'data' => $platformStats->pluck('total_orders')->toArray(),
                        'backgroundColor' => [
                            '#3B82F6', // blue-500
                            '#10B981', // green-500
                            '#F59E0B', // amber-500
                            '#8B5CF6', // purple-500
                            '#EC4899', // pink-500
                            '#EF4444', // red-500
                        ],
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom'
                    ]
                ]
            ]
        ];

        // Recent activity dengan konversi timezone
        $recentBatches = OrderLabel::query()
            ->select([
                'batch_no',
                'three_pl_id',
                DB::raw('COUNT(*) as total_pages'),
                DB::raw('SUM(CASE WHEN printed_at IS NOT NULL THEN 1 ELSE 0 END) as printed_count'),
                DB::raw('MAX(created_at) as import_date')
            ])
            ->with('threePl')
            ->where('saved', 1)
            ->whereNotNull('batch_no')
            ->when($start && $end, function($q) use ($start, $end) {
                $utcStart = $start->copy()->timezone('UTC');
                $utcEnd = $end->copy()->timezone('UTC');
                return $q->whereBetween('order_date', [$utcStart, $utcEnd]);
            })
            ->groupBy('batch_no', 'three_pl_id')
            ->orderByDesc('import_date')
            ->limit(6)
            ->get();

        // Status summary dengan konversi timezone
        $statusSummary = OrderLabel::query()
            ->select([
                DB::raw("CASE
                    WHEN printed_at IS NOT NULL THEN 'printed'
                    WHEN status = 'open' THEN 'open'
                    WHEN status = 'processing' THEN 'processing'
                    WHEN status = 'completed' THEN 'completed'
                    ELSE 'other'
                END as status_group"),
                DB::raw('COUNT(*) as count')
            ])
            ->where('saved', 1)
            ->when($start && $end, function($q) use ($start, $end) {
                $utcStart = $start->copy()->timezone('UTC');
                $utcEnd = $end->copy()->timezone('UTC');
                return $q->whereBetween('order_date', [$utcStart, $utcEnd]);
            })
            ->groupBy('status_group')
            ->get()
            ->pluck('count', 'status_group');

        // Print status for chart
        $printStatusData = [
            'printed' => $totalPrinted,
            'pending' => $totalNotPrinted,
        ];

        // Performance metrics
        $performanceMetrics = [
            'avg_print_time' => $this->calculateAvgPrintTime($start, $end),
            'peak_hour' => $this->getPeakPrintHour($start, $end),
            'success_rate' => $printEfficiency,
        ];

        return [
            'totalOrders' => $totalOrders,
            'totalPrinted' => $totalPrinted,
            'totalNotPrinted' => $totalNotPrinted,
            'totalBatches' => $totalBatches,
            'todayPrinted' => $todayPrinted,
            'todayPending' => $todayPending,
            'avgPrintCount' => round($avgPrintCount, 1),
            'printEfficiency' => $printEfficiency,
            'platformStats' => $platformStats,
            'platformChartData' => $this->platformChartData,
            'platformPieData' => $this->platformPieData,
            'recentBatches' => $recentBatches,
            'statusSummary' => $statusSummary,
            'printStatusData' => $printStatusData,
            'performanceMetrics' => $performanceMetrics,
            'period' => $this->period,
            'currentDate' => $now->format('d/m/Y'),
        ];
    }

    private function calculateAvgPrintTime($start, $end): string
    {
        $avgTime = OrderLabel::query()
            ->where('saved', 1)
            ->whereNotNull('printed_at')
            ->whereNotNull('created_at')
            ->when($start && $end, function($q) use ($start, $end) {
                $utcStart = $start->copy()->timezone('UTC');
                $utcEnd = $end->copy()->timezone('UTC');
                return $q->whereBetween('order_date', [$utcStart, $utcEnd]);
            })
            ->select(DB::raw('AVG(EXTRACT(EPOCH FROM (printed_at - created_at))) as avg_seconds'))
            ->value('avg_seconds');

        if (!$avgTime) return 'N/A';

        $hours = floor($avgTime / 3600);
        $minutes = floor(($avgTime % 3600) / 60);

        if ($hours > 0) {
            return "{$hours} jam {$minutes} menit";
        }

        return "{$minutes} menit";
    }

    private function getPeakPrintHour($start, $end): string
    {
        $peakHour = OrderLabel::query()
            ->where('saved', 1)
            ->whereNotNull('printed_at')
            ->when($start && $end, function($q) use ($start, $end) {
                $utcStart = $start->copy()->timezone('UTC');
                $utcEnd = $end->copy()->timezone('UTC');
                return $q->whereBetween('order_date', [$utcStart, $utcEnd]);
            })
            ->select(DB::raw("EXTRACT(HOUR FROM printed_at AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Jakarta') as hour, COUNT(*) as count"))
            ->groupBy(DB::raw("EXTRACT(HOUR FROM printed_at AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Jakarta')"))
            ->orderByDesc('count')
            ->value('hour');

        return $peakHour !== null ? sprintf("%02d:00", $peakHour) : 'N/A';
    }
}; ?>

<div class="space-y-6 dark:bg-gray-900 dark:text-gray-100 min-h-screen transition-colors duration-200">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors">Label Printing Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400 transition-colors">Monitor produksi dan distribusi label order</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 transition-colors">
                <span class="inline-flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    Waktu: UTC+7 (Jakarta/Bangkok) - {{ $currentDate }}
                </span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="$set('period', 'today')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $period === 'today'
                    ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm' }}">
                Hari Ini
            </button>
            <button wire:click="$set('period', 'week')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $period === 'week'
                    ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm' }}">
                Minggu Ini
            </button>
            <button wire:click="$set('period', 'month')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $period === 'month'
                    ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm' }}">
                Bulan Ini
            </button>
            <button wire:click="$set('period', 'all')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $period === 'all'
                    ? 'bg-blue-600 dark:bg-blue-500 text-white shadow-md'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 hover:shadow-sm' }}">
                Semua
            </button>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Orders --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md dark:hover:shadow-gray-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors">Total Order</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 transition-colors">{{ number_format($totalOrders) }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-600 transition-colors">
                <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Periode: {{ ucfirst($period) }}</p>
            </div>
        </div>

        {{-- Printed Orders --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md dark:hover:shadow-gray-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors">Tercetak</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2 transition-colors">{{ number_format($totalPrinted) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 transition-colors">{{ $printEfficiency }}% efisiensi</p>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-600 transition-colors">
                <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">{{ $todayPrinted }} dicetak hari ini</p>
            </div>
        </div>

        {{-- Pending Orders --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md dark:hover:shadow-gray-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors">Menunggu</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2 transition-colors">{{ number_format($totalNotPrinted) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 transition-colors">Perlu tindakan</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-600 transition-colors">
                <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">{{ $todayPending }} baru hari ini</p>
            </div>
        </div>

        {{-- Performance --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200 hover:shadow-md dark:hover:shadow-gray-900/70">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300 transition-colors">Performa</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-2 transition-colors">{{ $performanceMetrics['avg_print_time'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 transition-colors">Rata-rata waktu cetak</p>
                </div>
                <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-600 transition-colors">
                <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Jam puncak: {{ $performanceMetrics['peak_hour'] }}</p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Platform Comparison Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">Perbandingan Total Label per Platform</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Visualisasi distribusi label dari setiap platform</p>
                    </div>
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>

                @if($platformStats->isEmpty())
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="mt-4 text-gray-500 dark:text-gray-400">Tidak ada data untuk ditampilkan</p>
                    </div>
                @else
                    <div class="h-80 w-full relative">
                        <x-chart wire:model="platformChartData" />
                    </div>

                    {{-- Platform Summary Cards --}}
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                        @foreach($platformStats->take(3) as $platform)
                        <div class="text-center p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50 transition-colors">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $platform->threePl->name ?? 'Unknown' }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($platform->total_orders) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">label</p>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Platform Performance --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">Performa Platform</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-300 transition-colors bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">{{ $platformStats->count() }} platform</span>
                </div>

                <div class="space-y-4">
                    @forelse($platformStats as $platform)
                    <div class="flex items-center justify-between p-4 rounded-lg border border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/70 transition-all duration-200 cursor-pointer group">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center transition-colors group-hover:bg-blue-100 dark:group-hover:bg-blue-800/50">
                                <span class="text-blue-600 dark:text-blue-300 font-semibold transition-colors">
                                    {{ substr($platform->threePl->name ?? 'NA', 0, 2) }}
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-300">{{ $platform->threePl->name ?? 'Unknown Platform' }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 transition-colors">{{ number_format($platform->total_orders) }} order</p>
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="flex items-center space-x-4">
                                <div>
                                    <p class="font-semibold text-green-600 dark:text-green-400 transition-colors">{{ $platform->printed_orders }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Tercetak</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-amber-600 dark:text-amber-400 transition-colors">{{ $platform->pending_orders }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Menunggu</p>
                                </div>
                            </div>
                            @php
                                $efficiency = $platform->total_orders > 0 ? round(($platform->printed_orders / $platform->total_orders) * 100) : 0;
                            @endphp
                            <div class="mt-2">
                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 dark:bg-green-400 rounded-full transition-all duration-500" style="width: {{ $efficiency }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-300 mt-1 transition-colors">{{ $efficiency }}% efisiensi</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400 transition-colors">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-gray-400 dark:text-gray-300 transition-colors">Tidak ada data platform</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Batches --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">Batch Terbaru</h3>
                    <a href="{{ route('order-label.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium transition-colors inline-flex items-center gap-1">
                        Lihat Semua
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-600">
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Batch No</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Platform</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Total</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Progress</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBatches as $batch)
                            <tr class="border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors duration-150">
                                <td class="py-3 px-4">
                                    <span class="inline-block px-3 py-1 text-xs font-mono bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded-full border border-blue-100 dark:border-blue-800 transition-colors">
                                        {{ $batch->batch_no }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center transition-colors">
                                            <span class="text-xs font-semibold text-purple-600 dark:text-purple-300 transition-colors">
                                                {{ substr($batch->threePl->name ?? 'N', 0, 1) }}
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 transition-colors">{{ $batch->threePl->name ?? 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-900 dark:text-white transition-colors">{{ $batch->total_pages }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1 transition-colors">halaman</span>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $progress = $batch->total_pages > 0 ? round(($batch->printed_count / $batch->total_pages) * 100) : 0;
                                        $color = $progress >= 80 ? 'bg-green-500 dark:bg-green-400' : ($progress >= 50 ? 'bg-blue-500 dark:bg-blue-400' : 'bg-amber-500 dark:bg-amber-400');
                                        $textColor = $progress >= 80 ? 'text-green-600 dark:text-green-300' : ($progress >= 50 ? 'text-blue-600 dark:text-blue-300' : 'text-amber-600 dark:text-amber-300');
                                    @endphp
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full {{ $color }} rounded-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <span class="text-sm font-medium {{ $textColor }} transition-colors">
                                            {{ $progress }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600 dark:text-gray-300 transition-colors">
                                    {{ $batch->import_date ? \Carbon\Carbon::parse($batch->import_date)->format('d/m/Y') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-300 transition-colors">
                                    <svg class="w-8 h-8 mx-auto text-gray-300 dark:text-gray-600 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="dark:text-gray-400">Tidak ada batch tersedia</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Platform Distribution Pie Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white transition-colors">Distribusi Platform</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Persentase label per platform</p>
                    </div>
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                        </svg>
                    </div>
                </div>

                @if($platformStats->isEmpty())
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada data</p>
                    </div>
                @else
                    <div class="h-64 w-full flex items-center justify-center relative">
                        <x-chart wire:model="platformPieData" />
                    </div>

                    {{-- Legend --}}
                    <div class="mt-6 space-y-2">
                        @foreach($platformStats as $index => $platform)
                        @php
                            $colors = ['bg-blue-500', 'bg-green-500', 'bg-amber-500', 'bg-purple-500', 'bg-pink-500', 'bg-red-500'];
                            $percentage = $totalOrders > 0 ? round(($platform->total_orders / $totalOrders) * 100, 1) : 0;
                        @endphp
                        <div class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full {{ $colors[$index % 6] }}"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $platform->threePl->name ?? 'Unknown' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $percentage }}%</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">({{ number_format($platform->total_orders) }})</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Print Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 transition-colors">Status Pencetakan</h3>

                <div class="space-y-6">
                    {{-- Printed vs Pending --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Tercetak</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400 transition-colors">{{ number_format($printStatusData['printed']) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Menunggu</p>
                                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 transition-colors">{{ number_format($printStatusData['pending']) }}</p>
                            </div>
                        </div>

                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            @php
                                $total = $printStatusData['printed'] + $printStatusData['pending'];
                                $printedPercentage = $total > 0 ? ($printStatusData['printed'] / $total) * 100 : 0;
                            @endphp
                            <div class="h-full bg-green-500 dark:bg-green-400 rounded-full transition-all duration-700" style="width: {{ $printedPercentage }}%"></div>
                        </div>
                        <p class="text-center text-sm text-gray-600 dark:text-gray-300 mt-2 transition-colors">
                            {{ number_format($printedPercentage, 1) }}% sudah tercetak
                        </p>
                    </div>

                    {{-- Status Summary --}}
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3 transition-colors">Ringkasan Status</h4>
                        <div class="space-y-2">
                            @foreach(['printed' => 'Tercetak', 'open' => 'Terbuka', 'processing' => 'Diproses', 'completed' => 'Selesai', 'other' => 'Lainnya'] as $key => $label)
                                @if(isset($statusSummary[$key]) && $statusSummary[$key] > 0)
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors duration-150 cursor-pointer">
                                    <div class="flex items-center space-x-3">
                                        @php
                                            $statusIcons = [
                                                'printed' => '<svg class="w-4 h-4 text-green-500 dark:text-green-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>',
                                                'open' => '<svg class="w-4 h-4 text-amber-500 dark:text-amber-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>',
                                                'processing' => '<svg class="w-4 h-4 text-blue-500 dark:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>',
                                                'completed' => '<svg class="w-4 h-4 text-purple-500 dark:text-purple-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                                'other' => '<svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                            ];
                                        @endphp
                                        <div>{!! $statusIcons[$key] ?? $statusIcons['other'] !!}</div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 transition-colors">{{ $label }}</span>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-medium text-gray-900 dark:text-white transition-colors">{{ number_format($statusSummary[$key]) }}</span>
                                        @php
                                            $percentage = $totalOrders > 0 ? round(($statusSummary[$key] / $totalOrders) * 100) : 0;
                                        @endphp
                                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">
                                            {{ $percentage }}%
                                        </span>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Metrics --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 transition-colors">Metrik Performa</h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-100 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-800/40 transition-colors duration-150 cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Rata-rata Cetak</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white transition-colors">{{ $avgPrintCount }}x</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/30 rounded-lg border border-green-100 dark:border-green-800 hover:bg-green-100 dark:hover:bg-green-800/40 transition-colors duration-150 cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-800 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Jam Puncak</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white transition-colors">{{ $performanceMetrics['peak_hour'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg border border-purple-100 dark:border-purple-800 hover:bg-purple-100 dark:hover:bg-purple-800/40 transition-colors duration-150 cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-800 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">Total Batch</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white transition-colors">{{ number_format($totalBatches) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-gray-900/50 p-6 border border-gray-100 dark:border-gray-700 transition-all duration-200">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 transition-colors">Aksi Cepat</h3>

                <div class="space-y-2">
                    <a href="{{ route('order-label.import') }}"
                       class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-blue-900/40 hover:border-blue-200 dark:hover:border-blue-700 transition-all duration-200 cursor-pointer group">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300 transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-300">Import PDF</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-colors group-hover:text-blue-500 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <a href="{{ route('order-label.index') }}"
                       class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-green-50 dark:hover:bg-green-900/40 hover:border-green-200 dark:hover:border-green-700 transition-all duration-200 cursor-pointer group">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-800 flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300 transition-colors group-hover:text-green-600 dark:group-hover:text-green-300">Lihat Semua Order</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-colors group-hover:text-green-500 dark:group-hover:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>

                    <button wire:click="$refresh"
                       class="w-full flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-amber-50 dark:hover:bg-amber-900/40 hover:border-amber-200 dark:hover:border-amber-700 transition-all duration-200 cursor-pointer group">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-800 flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-700 transition-colors">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <span class="font-medium text-gray-700 dark:text-gray-300 transition-colors group-hover:text-amber-600 dark:group-hover:text-amber-300">Refresh Dashboard</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-colors group-hover:text-amber-500 dark:group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>


</div>
