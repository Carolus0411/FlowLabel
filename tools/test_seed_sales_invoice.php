<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $contact = App\Models\Contact::find(1);
    var_dump($contact->id ?? null);
    $date = date('Y-m-d');
    $code = App\Helpers\Code::auto('CC', $date);
    var_dump($code);
    $invoice = App\Models\SalesInvoice::create([
        'code' => $code,
        'invoice_date' => $date,
        'due_date' => $date,
        'contact_id' => $contact->id,
        'top' => 0,
        'ppn_id' => null,
        'pph_id' => null,
        'saved' => 1,
        'status' => 'open'
    ]);
    var_dump('invoice created', $invoice->id);

    $detail = App\Models\SalesInvoiceDetail::create([
        'sales_invoice_id' => $invoice->id,
        'service_charge_id' => 1,
        'note' => 'test',
        'qty' => 1,
        'uom_id' => 1,
        'currency_id' => 1,
        'currency_rate' => 1,
        'price' => 100,
        'foreign_amount' => 100,
        'amount' => 100,
    ]);
    var_dump('detail created', $detail->id);
} catch (Exception $e) {
    echo 'Exception: ' . $e->getMessage() . PHP_EOL;
}
