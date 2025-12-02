<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Helpers\Code;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\ServiceCharge;
use App\Models\Uom;
use App\Models\Currency;
use App\Models\Contact;
use App\Enums\InvoiceType;

class SalesInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $contactIds = Contact::query()->pluck('id')->toArray();
        $serviceCharges = ServiceCharge::query()->where('is_active', true)->pluck('id')->toArray();
        $uoms = Uom::query()->pluck('id')->toArray();
        $currencies = Currency::query()->pluck('id')->toArray();
        $ppns = DB::table('ppn')->pluck('id')->toArray();
        $pphs = DB::table('pph')->pluck('id')->toArray();

        if (empty($contactIds) || empty($serviceCharges) || empty($uoms) || empty($currencies)) {
            // not enough data seeded to create sales invoice
            return;
        }

        DB::beginTransaction();
        try {
            $total = 50; // generate 50 sales invoices

            for ($i = 0; $i < $total; $i++) {
                $date = $faker->dateTimeBetween('-1 year', 'now');
                $dateStr = $date->format('Y-m-d');

                $invoiceType = $faker->randomElement(array_map(fn($c) => $c->value, InvoiceType::cases()));
                $contactId = $faker->randomElement($contactIds);
                $top = $faker->randomElement([0,7,14,30]);
                $dueDate = (clone $date)->modify("+$top days");

                $code = Code::auto($invoiceType, $dateStr);

                // Create sales invoice header
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

                // create details
                $detailsCount = $faker->numberBetween(1, 5);
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
                    // optionally randomize partial payments
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

                // If closed, dispatch event to generate journals like UI would
                if ($status == 'close') {
                    \App\Events\SalesInvoiceClosed::dispatch($invoice);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('SalesInvoiceSeeder: ' . $e->getMessage());
        }
    }
}
