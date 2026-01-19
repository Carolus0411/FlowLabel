<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\CashIn;
use App\Models\CashInDetail;
use App\Models\CashOut;
use App\Models\CashOutDetail;
use App\Models\BankIn;
use App\Models\BankInDetail;
use App\Models\BankOut;
use App\Models\BankOutDetail;
use App\Models\Contact;
use App\Models\Supplier;
use App\Models\CashAccount;
use App\Models\BankAccount;
use App\Models\Coa;
use App\Models\Currency;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Authenticate as first user for model events
        $user = \App\Models\User::first();
        if (!$user) {
            $this->command->error('No user found! Please run UserSeeder first!');
            return;
        }
        auth()->login($user);

        // Get necessary related data
        $contacts = Contact::all();
        $suppliers = Supplier::all();
        $cashAccount = CashAccount::first();
        $bankAccount = BankAccount::first();
        $currency = Currency::first();
        $coas = Coa::take(5)->get();

        if ($coas->count() < 2) {
            $this->command->error('Not enough COA records! Please run CoaSeeder first!');
            return;
        }

        $revenueCoa = $coas[0];
        $expenseCoa = $coas[1];

        if ($contacts->isEmpty() || $suppliers->isEmpty() || !$cashAccount || !$bankAccount || !$currency) {
            $this->command->error('Please run ContactSeeder, SupplierSeeder, CashAccountSeeder, BankAccountSeeder, and CurrencySeeder first!');
            return;
        }

        $this->command->info('Seeding Sales Orders...');
        $this->seedSalesOrders($contacts);

        $this->command->info('Seeding Sales Invoices...');
        $this->seedSalesInvoices($contacts);

        $this->command->info('Seeding Purchase Orders...');
        $this->seedPurchaseOrders($suppliers);

        $this->command->info('Seeding Purchase Invoices...');
        $this->seedPurchaseInvoices($suppliers);

        $this->command->info('Seeding Cash In...');
        $this->seedCashIn($contacts, $cashAccount, $currency, $revenueCoa);

        $this->command->info('Seeding Cash Out...');
        $this->seedCashOut($suppliers, $cashAccount, $currency, $expenseCoa);

        $this->command->info('Seeding Bank In...');
        $this->seedBankIn($contacts, $bankAccount, $currency, $revenueCoa);

        $this->command->info('Seeding Bank Out...');
        $this->seedBankOut($suppliers, $bankAccount, $currency, $expenseCoa);

        $this->command->info('Transaction seeding completed successfully!');
    }

    private function seedSalesOrders($contacts)
    {
        $lastId = SalesOrder::max('id') ?? 0;

        for ($i = 1; $i <= 20; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $dueDate = Carbon::parse($date)->addDays(30)->format('Y-m-d');
            $orderAmount = rand(1000000, 30000000);
            $num = $lastId + $i;

            SalesOrder::create([
                'code' => 'SO-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'order_date' => $date,
                'due_date' => $dueDate,
                'contact_id' => $contacts->random()->id,
                'note' => 'Sales Order ' . $num . ' - ' . fake()->sentence(5),
                'dpp_amount' => $orderAmount,
                'ppn_id' => null,
                'ppn_amount' => 0,
                'pph_id' => null,
                'pph_amount' => 0,
                'order_amount' => $orderAmount,
                'balance_amount' => $orderAmount,
                'payment_status' => 'unpaid',
                'status' => rand(0, 1) ? 'close' : 'open',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    private function seedSalesInvoices($contacts)
    {
        $lastId = SalesInvoice::max('id') ?? 0;

        for ($i = 1; $i <= 30; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $dueDate = Carbon::parse($date)->addDays(30)->format('Y-m-d');
            $invoiceAmount = rand(1000000, 50000000);
            $balanceAmount = rand(0, 1) ? rand(0, $invoiceAmount) : $invoiceAmount;
            $num = $lastId + $i;

            $paymentStatus = 'unpaid';
            if ($balanceAmount == 0) {
                $paymentStatus = 'paid';
            } elseif ($balanceAmount < $invoiceAmount) {
                $paymentStatus = 'outstanding';
            }

            SalesInvoice::create([
                'code' => 'SI-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'invoice_date' => $date,
                'due_date' => $dueDate,
                'contact_id' => $contacts->random()->id,
                'note' => 'Sales Invoice ' . $num . ' - ' . fake()->sentence(5),
                'dpp_amount' => $invoiceAmount,
                'ppn_id' => null,
                'ppn_amount' => 0,
                'pph_id' => null,
                'pph_amount' => 0,
                'invoice_amount' => $invoiceAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'status' => 'close',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    private function seedPurchaseOrders($suppliers)
    {
        $lastId = PurchaseOrder::max('id') ?? 0;

        for ($i = 1; $i <= 20; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $dueDate = Carbon::parse($date)->addDays(30)->format('Y-m-d');
            $orderAmount = rand(1000000, 30000000);
            $num = $lastId + $i;

            PurchaseOrder::create([
                'code' => 'PO-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'order_date' => $date,
                'due_date' => $dueDate,
                'supplier_id' => $suppliers->random()->id,
                'note' => 'Purchase Order ' . $num . ' - ' . fake()->sentence(5),
                'dpp_amount' => $orderAmount,
                'ppn_id' => null,
                'ppn_amount' => 0,
                'pph_id' => null,
                'pph_amount' => 0,
                'order_amount' => $orderAmount,
                'balance_amount' => $orderAmount,
                'payment_status' => 'unpaid',
                'status' => rand(0, 1) ? 'close' : 'open',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    private function seedPurchaseInvoices($suppliers)
    {
        $lastId = PurchaseInvoice::max('id') ?? 0;

        for ($i = 1; $i <= 30; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $dueDate = Carbon::parse($date)->addDays(30)->format('Y-m-d');
            $invoiceAmount = rand(1000000, 40000000);
            $balanceAmount = rand(0, 1) ? rand(0, $invoiceAmount) : $invoiceAmount;
            $num = $lastId + $i;

            $paymentStatus = 'unpaid';
            if ($balanceAmount == 0) {
                $paymentStatus = 'paid';
            } elseif ($balanceAmount < $invoiceAmount) {
                $paymentStatus = 'outstanding';
            }

            PurchaseInvoice::create([
                'code' => 'PI-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'invoice_date' => $date,
                'due_date' => $dueDate,
                'supplier_id' => $suppliers->random()->id,
                'note' => 'Purchase Invoice ' . $num . ' - ' . fake()->sentence(5),
                'dpp_amount' => $invoiceAmount,
                'ppn_id' => null,
                'ppn_amount' => 0,
                'pph_id' => null,
                'pph_amount' => 0,
                'invoice_amount' => $invoiceAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'status' => 'close',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    private function seedCashIn($contacts, $cashAccount, $currency, $revenueCoa)
    {
        $lastId = CashIn::max('id') ?? 0;

        for ($i = 1; $i <= 25; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $totalAmount = rand(500000, 10000000);
            $num = $lastId + $i;

            $cashIn = CashIn::create([
                'code' => 'CI-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'date' => $date,
                'note' => 'Cash In ' . $num . ' - ' . fake()->sentence(3),
                'cash_account_id' => $cashAccount->id,
                'contact_id' => $contacts->random()->id,
                'total_amount' => $totalAmount,
                'status' => 'close',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Add details - Revenue account
            CashInDetail::create([
                'cash_in_id' => $cashIn->id,
                'coa_code' => $revenueCoa->code,
                'currency_id' => $currency->id,
                'currency_rate' => 1,
                'foreign_amount' => $totalAmount,
                'amount' => $totalAmount,
                'note' => 'Revenue from customer',
            ]);
        }
    }

    private function seedCashOut($suppliers, $cashAccount, $currency, $expenseCoa)
    {
        $lastId = CashOut::max('id') ?? 0;

        for ($i = 1; $i <= 25; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $totalAmount = rand(500000, 8000000);
            $num = $lastId + $i;

            $cashOut = CashOut::create([
                'code' => 'CO-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'date' => $date,
                'note' => 'Cash Out ' . $num . ' - ' . fake()->sentence(3),
                'cash_account_id' => $cashAccount->id,
                'supplier_id' => $suppliers->random()->id,
                'total_amount' => $totalAmount,
                'status' => 'close',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Add details - Expense account
            CashOutDetail::create([
                'cash_out_id' => $cashOut->id,
                'coa_code' => $expenseCoa->code,
                'currency_id' => $currency->id,
                'currency_rate' => 1,
                'foreign_amount' => $totalAmount,
                'amount' => $totalAmount,
                'note' => 'Payment for supplier',
            ]);
        }
    }

    private function seedBankIn($contacts, $bankAccount, $currency, $revenueCoa)
    {
        $lastId = BankIn::max('id') ?? 0;

        for ($i = 1; $i <= 25; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $totalAmount = rand(1000000, 20000000);
            $num = $lastId + $i;

            $bankIn = BankIn::create([
                'code' => 'BI-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'date' => $date,
                'note' => 'Bank In ' . $num . ' - ' . fake()->sentence(3),
                'bank_account_id' => $bankAccount->id,
                'contact_id' => $contacts->random()->id,
                'total_amount' => $totalAmount,
                'status' => 'close',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Add details - Revenue account
            BankInDetail::create([
                'bank_in_id' => $bankIn->id,
                'coa_code' => $revenueCoa->code,
                'currency_id' => $currency->id,
                'currency_rate' => 1,
                'foreign_amount' => $totalAmount,
                'amount' => $totalAmount,
                'note' => 'Bank transfer from customer',
            ]);
        }
    }

    private function seedBankOut($suppliers, $bankAccount, $currency, $expenseCoa)
    {
        $lastId = BankOut::max('id') ?? 0;

        for ($i = 1; $i <= 25; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365))->format('Y-m-d');
            $totalAmount = rand(1000000, 15000000);
            $num = $lastId + $i;

            $bankOut = BankOut::create([
                'code' => 'BO-2025-' . str_pad($num, 5, '0', STR_PAD_LEFT),
                'date' => $date,
                'note' => 'Bank Out ' . $num . ' - ' . fake()->sentence(3),
                'bank_account_id' => $bankAccount->id,
                'supplier_id' => $suppliers->random()->id,
                'total_amount' => $totalAmount,
                'status' => 'close',
                'saved' => 1,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Add details - Expense account
            BankOutDetail::create([
                'bank_out_id' => $bankOut->id,
                'coa_code' => $expenseCoa->code,
                'currency_id' => $currency->id,
                'currency_rate' => 1,
                'foreign_amount' => $totalAmount,
                'amount' => $totalAmount,
                'note' => 'Bank transfer to supplier',
            ]);
        }
    }
}
