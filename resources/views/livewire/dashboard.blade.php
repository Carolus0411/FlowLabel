<?php

use Livewire\Volt\Component;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\Contact;
use App\Models\CashIn;
use App\Models\CashOut;
use App\Models\BankIn;
use App\Models\BankOut;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

new class extends Component {
    public $timeframe = 'month';

    public function with(): array
    {
        // Check if required tables exist
        $hasSalesInvoice = Schema::hasTable('sales_invoice');
        $hasPurchaseInvoice = Schema::hasTable('purchase_invoice');
        $hasSalesOrder = Schema::hasTable('sales_order');
        $hasPurchaseOrder = Schema::hasTable('purchase_order');
        $hasContact = Schema::hasTable('contact');
        $hasCashIn = Schema::hasTable('cash_in');
        $hasCashOut = Schema::hasTable('cash_out');
        $hasBankIn = Schema::hasTable('bank_in');
        $hasBankOut = Schema::hasTable('bank_out');

        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        if ($this->timeframe === 'week') {
            $start = $now->copy()->startOfWeek();
            $end = $now->copy()->endOfWeek();
        } elseif ($this->timeframe === 'quarter') {
            $start = $now->copy()->startOfQuarter();
            $end = $now->copy()->endOfQuarter();
        } elseif ($this->timeframe === 'year') {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
        }

        // Financial Stats
        $revenue = $hasSalesInvoice ? SalesInvoice::whereBetween('invoice_date', [$start, $end])->sum('invoice_amount') : 0;
        $expenses = $hasPurchaseInvoice ? PurchaseInvoice::whereBetween('invoice_date', [$start, $end])->sum('invoice_amount') : 0;
        $netProfit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        // Transaction Counts
        $salesOrderCount = $hasSalesOrder ? SalesOrder::whereBetween('order_date', [$start, $end])->count() : 0;
        $purchaseOrderCount = $hasPurchaseOrder ? PurchaseOrder::whereBetween('order_date', [$start, $end])->count() : 0;
        $activeClients = $hasContact ? Contact::count() : 0;

        // Cash Flow
        $totalCashIn = $hasCashIn ? CashIn::whereBetween('date', [$start, $end])->sum('total_amount') : 0;
        $totalCashOut = $hasCashOut ? CashOut::whereBetween('date', [$start, $end])->sum('total_amount') : 0;
        $totalBankIn = $hasBankIn ? BankIn::whereBetween('date', [$start, $end])->sum('total_amount') : 0;
        $totalBankOut = $hasBankOut ? BankOut::whereBetween('date', [$start, $end])->sum('total_amount') : 0;

        // Pending & Outstanding
        $pendingInvoices = $hasSalesInvoice ? SalesInvoice::where('payment_status', '!=', 'paid')->whereBetween('invoice_date', [$start, $end]) : collect();
        $pendingCount = $hasSalesInvoice ? $pendingInvoices->count() : 0;
        $pendingAmount = $hasSalesInvoice ? $pendingInvoices->sum('balance_amount') : 0;

        $overdueInvoices = $hasSalesInvoice ? SalesInvoice::where('payment_status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count() : 0;

        // Recent Transactions - Combined Sales & Purchase
        $sales = $hasSalesInvoice ? SalesInvoice::with('contact')
            ->select('id', 'invoice_date as date', 'code', 'invoice_amount as amount', 'contact_id', 'created_at', DB::raw("'Sales' as type"), 'note as description')
            ->latest('invoice_date')
            ->take(3)
            ->get() : collect();

        $purchases = $hasPurchaseInvoice ? PurchaseInvoice::with('supplier')
            ->select('id', 'invoice_date as date', 'code', 'invoice_amount as amount', 'supplier_id', 'created_at', DB::raw("'Purchase' as type"), 'note as description')
            ->latest('invoice_date')
            ->take(2)
            ->get() : collect();

        $recentTransactions = $sales->merge($purchases)->sortByDesc('date')->take(5);

        // Monthly Chart Data
        $monthlyStats = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthStart = Carbon::createFromDate(null, $i, 1)->startOfMonth();
            $monthEnd = Carbon::createFromDate(null, $i, 1)->endOfMonth();

            $monthlyRevenue = $hasSalesInvoice ? SalesInvoice::whereBetween('invoice_date', [$monthStart, $monthEnd])->sum('invoice_amount') : 0;
            $monthlyExpense = $hasPurchaseInvoice ? PurchaseInvoice::whereBetween('invoice_date', [$monthStart, $monthEnd])->sum('invoice_amount') : 0;

            $monthlyStats[] = [
                'month' => $monthStart->format('M'),
                'revenue' => $monthlyRevenue,
                'expense' => $monthlyExpense,
            ];
        }

        $maxVal = 1;
        foreach ($monthlyStats as $stat) {
            $maxVal = max($maxVal, $stat['revenue'], $stat['expense']);
        }

        foreach ($monthlyStats as &$stat) {
            $stat['revenuePct'] = $maxVal > 0 ? ($stat['revenue'] / $maxVal) * 100 : 0;
            $stat['expensePct'] = $maxVal > 0 ? ($stat['expense'] / $maxVal) * 100 : 0;
        }

        // Expense Breakdown - Mock data based on purchase categories
        $totalExpenses = $expenses > 0 ? $expenses : 1;
        $expenseBreakdown = [
            ['category' => 'Pembelian Barang', 'amount' => $expenses * 0.55, 'percentage' => 55],
            ['category' => 'Operasional', 'amount' => $expenses * 0.25, 'percentage' => 25],
            ['category' => 'Lain-lain', 'amount' => $expenses * 0.15, 'percentage' => 15],
            ['category' => 'Biaya Lainnya', 'amount' => $expenses * 0.05, 'percentage' => 5],
        ];

        // Accounts Receivable
        $accountsReceivable = $hasSalesInvoice ? SalesInvoice::with('contact')
            ->where('balance_amount', '>', 0)
            ->orderBy('due_date')
            ->take(4)
            ->get() : collect();

        $totalReceivable = $hasSalesInvoice ? SalesInvoice::sum('balance_amount') : 0;

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'netProfit' => $netProfit,
            'profitMargin' => $profitMargin,
            'salesOrderCount' => $salesOrderCount,
            'purchaseOrderCount' => $purchaseOrderCount,
            'activeClients' => $activeClients,
            'totalCashIn' => $totalCashIn,
            'totalCashOut' => $totalCashOut,
            'totalBankIn' => $totalBankIn,
            'totalBankOut' => $totalBankOut,
            'pendingCount' => $pendingCount,
            'pendingAmount' => $pendingAmount,
            'overdueInvoices' => $overdueInvoices,
            'recentTransactions' => $recentTransactions,
            'monthlyStats' => $monthlyStats,
            'expenseBreakdown' => $expenseBreakdown,
            'accountsReceivable' => $accountsReceivable,
            'totalReceivable' => $totalReceivable,
        ];
    }
}; ?>

<div class="bg-base-200">
    <div class="p-6 space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold">Dashboard Keuangan</h1>
                <p class="text-gray-600">{{ Carbon::now()->translatedFormat('l, d F Y') }}</p>
            </div>
            <div class="flex gap-3">
                <select wire:model.live="timeframe" class="select select-bordered">
                    <option value="month">Bulan Ini</option>
                    <option value="week">Minggu Ini</option>
                    <option value="quarter">Kuartal Ini</option>
                    <option value="year">Tahun Ini</option>
                </select>
                <button class="btn btn-outline gap-2">
                    <x-icon name="o-arrow-down-tray" class="h-5 w-5" />
                    Export
                </button>
                <button class="btn btn-primary gap-2">
                    <x-icon name="o-document-text" class="h-5 w-5" />
                    Buat Laporan
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Card 1: Total Revenue -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">TOTAL PENDAPATAN</div>
                    <div class="stat-value text-success">Rp {{ number_format($revenue / 1000000, 1) }}JT</div>
                    <div class="stat-desc">
                        <span class="badge badge-success">+12.5%</span> vs bulan lalu
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Expenses -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-error">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                    </div>
                    <div class="stat-title">TOTAL PENGELUARAN</div>
                    <div class="stat-value text-error">Rp {{ number_format($expenses / 1000000, 1) }}JT</div>
                    <div class="stat-desc">
                        <span class="badge badge-error">+3.2%</span> vs bulan lalu
                    </div>
                </div>
            </div>

            <!-- Card 3: Net Profit -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <div class="stat-title">LABA BERSIH</div>
                    <div class="stat-value text-primary">Rp {{ number_format($netProfit / 1000000, 1) }}JT</div>
                    <div class="stat-desc">
                        <span class="badge badge-primary">+18.7%</span> vs bulan lalu
                    </div>
                </div>
            </div>

            <!-- Card 4: Active Clients -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">KLIEN AKTIF</div>
                    <div class="stat-value text-secondary">{{ $activeClients }}</div>
                    <div class="stat-desc">
                        <span class="badge badge-secondary">+5 baru</span> bulan ini
                    </div>
                </div>
            </div>

            <!-- Card 5: Pending Invoices -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="stat-title">INVOICE PENDING</div>
                    <div class="stat-value text-warning">{{ $pendingCount }}</div>
                    <div class="stat-desc">Total: Rp {{ number_format($pendingAmount / 1000000, 1) }}JT</div>
                </div>
            </div>

            <!-- Card 6: Profit Margin -->
            <div class="stats shadow">
                <div class="stat">
                    <div class="stat-figure text-accent">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="stat-title">MARGIN LABA</div>
                    <div class="stat-value text-accent">{{ number_format($profitMargin, 1) }}%</div>
                    <div class="stat-desc">
                        <span class="badge badge-accent">+2.3%</span> vs bulan lalu
                    </div>
                </div>
            </div>

        </div>

        <!-- Chart & Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Chart -->
            <div class="lg:col-span-2 card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl">Tren Pendapatan & Pengeluaran</h2>
                    <p class="text-sm text-gray-600">Perbandingan bulanan tahun {{ Carbon::now()->year }}</p>

                    <div class="h-64 flex items-end justify-between gap-2 mt-6">
                        @foreach($monthlyStats as $stat)
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full flex gap-1 items-end h-52">
                                <div class="tooltip flex-1 bg-success hover:bg-success-focus transition-all rounded-t-lg cursor-pointer"
                                     style="height: {{ $stat['revenuePct'] }}%"
                                     data-tip="Pendapatan: Rp {{ number_format($stat['revenue'] / 1000000, 1) }}JT">
                                </div>
                                <div class="tooltip flex-1 bg-error hover:bg-error-focus transition-all rounded-t-lg cursor-pointer"
                                     style="height: {{ $stat['expensePct'] }}%"
                                     data-tip="Pengeluaran: Rp {{ number_format($stat['expense'] / 1000000, 1) }}JT">
                                </div>
                            </div>
                            <span class="text-xs font-bold">{{ $stat['month'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Expense Breakdown -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-2xl mb-2">Rincian Pengeluaran</h2>
                    <p class="text-sm text-gray-600 mb-4">Kategori bulan ini</p>

                    <div class="space-y-4">
                        @foreach($expenseBreakdown as $item)
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">{{ $item['category'] }}</span>
                                <span class="font-bold">Rp {{ number_format($item['amount'] / 1000000, 1) }}JT</span>
                            </div>
                            <progress class="progress progress-{{ ['primary', 'secondary', 'accent', 'warning'][$loop->index] }}" value="{{ $item['percentage'] }}" max="100"></progress>
                            <div class="text-right text-sm text-gray-500 mt-1">{{ $item['percentage'] }}%</div>
                        </div>
                        @endforeach
                    </div>

                    <div class="divider"></div>

                    <div class="flex justify-between items-center">
                        <span class="font-bold">Total</span>
                        <span class="text-xl font-bold">Rp {{ number_format($expenses / 1000000, 1) }}JT</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Tables -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Transactions -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="card-title text-2xl">Transaksi Terbaru</h2>
                            <p class="text-sm text-gray-600">Aktivitas keuangan terkini</p>
                        </div>
                        <button class="btn btn-ghost btn-sm">Lihat Semua →</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Deskripsi</th>
                                    <th>Kategori</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTransactions as $tx)
                                <tr class="hover">
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="badge badge-{{ $tx->type === 'Sales' ? 'success' : 'error' }} badge-sm"></div>
                                            <span class="text-sm">{{ Carbon::parse($tx->date)->format('d M') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-semibold">{{ $tx->code }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $tx->type === 'Sales' ? ($tx->contact->name ?? 'Unknown') : ($tx->supplier->name ?? 'Unknown') }}
                                        </div>
                                    </td>
                                    <td><span class="badge badge-outline">{{ $tx->type === 'Sales' ? 'Penjualan' : 'Pembelian' }}</span></td>
                                    <td class="font-bold text-{{ $tx->type === 'Sales' ? 'success' : 'error' }}">
                                        {{ $tx->type === 'Sales' ? '+' : '-' }}Rp {{ number_format($tx->amount / 1000000, 1) }}JT
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Receivables -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="card-title text-2xl">Piutang Usaha</h2>
                            <p class="text-sm text-gray-600">Invoice yang belum terbayar</p>
                        </div>
                        <button class="btn btn-ghost btn-sm">Kelola →</button>
                    </div>

                    <div class="space-y-3">
                        @foreach($accountsReceivable as $ar)
                        @php
                            $dueDate = Carbon::parse($ar->due_date);
                            $isOverdue = $dueDate->isPast();
                            $daysDiff = abs($dueDate->diffInDays(now()));
                        @endphp
                        <div class="card bg-base-200 hover:bg-base-300 transition-all">
                            <div class="card-body p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="font-bold">{{ $ar->contact->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500">{{ $ar->code }}</div>
                                    </div>
                                    <div class="badge badge-{{ $isOverdue ? 'error' : ($daysDiff <= 5 ? 'warning' : 'info') }}">
                                        {{ $isOverdue ? 'Terlambat ' . $daysDiff . ' hari' : $daysDiff . ' hari lagi' }}
                                    </div>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs">{{ $dueDate->format('d M Y') }}</span>
                                    <span class="font-bold">Rp {{ number_format($ar->balance_amount / 1000000, 1) }}JT</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="divider"></div>

                    <div class="alert alert-info">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <div class="font-bold">Total Piutang</div>
                            <div class="text-xl font-bold">Rp {{ number_format($totalReceivable / 1000000, 1) }}JT</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
