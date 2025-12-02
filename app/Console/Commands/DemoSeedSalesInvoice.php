<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Faker\Factory as Faker;
use App\Helpers\Code;
use Illuminate\Support\Facades\DB;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\SalesInvoiceDetail;
use App\Models\ServiceCharge;
use App\Models\Uom;
use App\Models\Currency;
use App\Models\Contact;

class DemoSeedSalesInvoice extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'demo:seed-sales-invoice {--count=50 : Total number of sales invoices to create} {--contacts= : Comma separated contact ids (default: all contacts)}';

    /**
     * The console command description.
     */
    protected $description = 'Generate demo Sales Invoice records with random data for specified contacts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $faker = Faker::create();

        $count = intval($this->option('count') ?? 50);
        $contactsOpt = $this->option('contacts');

        $contactIds = [];
        if (!empty($contactsOpt)) {
            $contactIds = array_filter(array_map('intval', explode(',', $contactsOpt)));
        } else {
            $contactIds = Contact::query()->pluck('id')->toArray();
        }

        if (empty($contactIds)) {
            $this->error('No contacts available for seeding. Please create or specify contact ids.');
            return 1;
        }

        $serviceCharges = ServiceCharge::query()->where('is_active', true)->pluck('id')->toArray();
        $uoms = Uom::query()->pluck('id')->toArray();
        $currencies = Currency::query()->pluck('id')->toArray();
        $ppns = DB::table('ppn')->pluck('id')->toArray();
        $pphs = DB::table('pph')->pluck('id')->toArray();

        if (empty($serviceCharges) || empty($uoms) || empty($currencies)) {
            $this->error('Missing required master data: service charges, uoms, or currencies. Please seed them first.');
            return 1;
        }

        // Ensure there's an authenticated user for model `creating` hooks that reference auth()->user()->id
        $user = User::first();
        if ($user) {
            auth()->setUser($user);
        }

        $this->info("Seeding $count sales invoices for contacts: " . implode(',', $contactIds));

        DB::beginTransaction();
        try {
            for ($i = 0; $i < $count; $i++) {
                $date = $faker->dateTimeBetween('-1 year', 'now');
                $dateStr = $date->format('Y-m-d');

                // Randomly choose a contact from the provided list
                $contactId = $faker->randomElement($contactIds);

                // Random invoice type from enum choices
                $invoiceTypeCases = \App\Enums\InvoiceType::cases();
                $invoiceType = $faker->randomElement(array_map(fn($c) => $c->value, $invoiceTypeCases));

                $top = $faker->randomElement([0, 7, 14, 30]);
                $dueDate = (clone $date)->modify("+$top days");

                // Generate code
                $code = Code::auto($invoiceType, $dateStr);

                $invoice = SalesInvoice::create([
                    'code' => $code,
                    'invoice_date' => $dateStr,
                    'due_date' => $dueDate->format('Y-m-d'),
                    'transport' => $faker->randomElement(['sea','air','land']),
                    'service_type' => $faker->randomElement(['SERVICE','PRODUCT']),
                    'invoice_type' => $invoiceType,
                    'note' => $faker->sentence(6),
                    'contact_id' => $contactId,
                    'top' => $top,
                    'ppn_id' => $faker->randomElement($ppns) ?? null,
                    'pph_id' => $faker->randomElement($pphs) ?? null,
                    'stamp_amount' => 0,
                    'dpp_amount' => 0,
                    'ppn_amount' => 0,
                    'pph_amount' => 0,
                    'invoice_amount' => 0,
                    'balance_amount' => 0,
                    'saved' => 1,
                    'status' => 'open',
                ]);

                $detailsCount = $faker->numberBetween(1, 4);
                $dppAmount = 0;
                for ($j = 0; $j < $detailsCount; $j++) {
                    $serviceChargeId = $faker->randomElement($serviceCharges);
                    $uomId = $faker->randomElement($uoms);
                    $currencyId = $faker->randomElement($currencies);
                    $qty = $faker->randomFloat(2, 1, 10);
                    $price = $faker->randomFloat(2, 100, 10000);
                    $foreignAmount = round($qty * $price, 2);
                    $amount = $foreignAmount; // assume rate 1

                    SalesInvoiceDetail::create([
                        'sales_invoice_id' => $invoice->id,
                        'service_charge_id' => $serviceChargeId,
                        'note' => $faker->sentence(4),
                        'qty' => $qty,
                        'uom_id' => $uomId,
                        'currency_id' => $currencyId,
                        'currency_rate' => 1,
                        'price' => $price,
                        'foreign_amount' => $foreignAmount,
                        'amount' => $amount,
                    ]);

                    $dppAmount += $amount;
                }

                $ppnId = $invoice->ppn_id;
                $pphId = $invoice->pph_id;
                $ppnValue = $ppnId ? DB::table('ppn')->where('id', $ppnId)->value('value') : 0;
                $pphValue = $pphId ? DB::table('pph')->where('id', $pphId)->value('value') : 0;

                $ppnAmount = round(($ppnValue/100) * $dppAmount, 2);
                $pphAmount = round(($pphValue/100) * $dppAmount, 2);
                $stampAmount = 0;
                $invoiceAmount = $dppAmount + $ppnAmount + $stampAmount;

                $status = $faker->randomElement(['open', 'close']);
                $balance = $invoiceAmount;
                if ($status == 'close') {
                    $paidPercentage = $faker->randomElement([0, 0, 0.25, 0.5, 1]);
                    $paid = round($invoiceAmount * $paidPercentage, 2);
                    $balance = $invoiceAmount - $paid;
                }

                $invoice->update([
                    'dpp_amount' => $dppAmount,
                    'ppn_amount' => $ppnAmount,
                    'pph_amount' => $pphAmount,
                    'stamp_amount' => $stampAmount,
                    'invoice_amount' => $invoiceAmount,
                    'balance_amount' => $balance,
                    'status' => $status,
                ]);

                // dispatch close event if closed
                if ($status == 'close') {
                    \App\Events\SalesInvoiceClosed::dispatch($invoice);
                }

                $this->info("Created invoice: {$invoice->code} for contact {$contactId} (status: {$status})");
            }

            DB::commit();

            $this->info('Sales invoice demo seeding finished.');
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to seed sales invoices: ' . $e->getMessage());
            return 1;
        }
    }
}
