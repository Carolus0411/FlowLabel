<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Livewire\Volt\Volt;
use App\Livewire\Home;
use App\Http\Controllers\PrintController;

Route::get('/', function () {
    return redirect()->route('home');
});

require __DIR__.'/auth.php';

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

/**
 * Handle GET requests to Livewire update endpoint gracefully.
 * Livewire's update endpoint expects POST, but accidental GET requests
 * (from crawlers or other clients) produce a MethodNotAllowedHttpException.
 * We return 404 for GET to /livewire/update to avoid noisy stack traces
 * and provide a friendlier response.
 */
Route::get('/livewire/update', function () {
    abort(404);
});

// Public route for downloading order label PDF (no auth required)
Route::get('/order-label-download/{path}', [\App\Http\Controllers\OrderLabelController::class, 'publicDownload'])
    ->where('path', '.*')
    ->name('order-label.public-download');

// Public route for downloading print label PDF (no auth required)
Route::get('/print-label-download/{path}', [\App\Http\Controllers\PrintLabelController::class, 'publicDownload'])
    ->where('path', '.*')
    ->name('print-label.public-download');

Route::prefix('cp')->middleware(['auth'])->group(function () {

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Volt::route('/dashboard', 'dashboard')->name('dashboard');

    Volt::route('/contact', 'contact.index')->name('contact.index');
    Volt::route('/contact/create', 'contact.create')->name('contact.create');
    Volt::route('/contact/{contact}/edit', 'contact.edit')->name('contact.edit');
    Volt::route('/contact/import', 'contact.import')->name('contact.import');

    Volt::route('/supplier', 'supplier.index')->name('supplier.index');
    Volt::route('/supplier/create', 'supplier.create')->name('supplier.create');
    Volt::route('/supplier/{supplier}/edit', 'supplier.edit')->name('supplier.edit');
    Volt::route('/supplier/import', 'supplier.import')->name('supplier.import');

    Volt::route('/currency', 'currency.index')->name('currency.index');
    Volt::route('/currency/create', 'currency.create')->name('currency.create');
    Volt::route('/currency/{currency}/edit', 'currency.edit')->name('currency.edit');
    Volt::route('/currency/import', 'currency.import')->name('currency.import');

    Volt::route('/uom', 'uom.index')->name('uom.index');
    Volt::route('/uom/create', 'uom.create')->name('uom.create');
    Volt::route('/uom/{uom}/edit', 'uom.edit')->name('uom.edit');
    Volt::route('/uom/import', 'uom.import')->name('uom.import');

    Volt::route('/item-type', 'item-type.index')->name('item-type.index');
    Volt::route('/item-type/create', 'item-type.create')->name('item-type.create');
    Volt::route('/item-type/{itemType}/edit', 'item-type.edit')->name('item-type.edit');

    Volt::route('/bank-name', 'bank.index')->name('bank.index');
    Volt::route('/bank-name/create', 'bank.create')->name('bank.create');
    Volt::route('/bank-name/{bank}/edit', 'bank.edit')->name('bank.edit');
    Volt::route('/bank-name/import', 'bank.import')->name('bank.import');

    Volt::route('/three-pl', 'three-pl.index')->name('three-pl.index');
    Volt::route('/three-pl/create', 'three-pl.create')->name('three-pl.create');
    Volt::route('/three-pl/{threePl}/edit', 'three-pl.edit')->name('three-pl.edit');
    Volt::route('/three-pl/import', 'three-pl.import')->name('three-pl.import');

    // Supplier template download route (not a Volt component, just a simple route)
    Route::get('/supplier/template', function () {
        $writer = Spatie\SimpleExcel\SimpleExcelWriter::streamDownload('Supplier Template.xlsx');
        // header row for template
        $writer->addRow([
            'code' => 'CODE',
            'name' => 'NAME',
            'contact_name' => 'CONTACT NAME',
            'address_1' => 'ADDRESS 1',
            'address_2' => 'ADDRESS 2',
            'telephone' => 'TELEPHONE',
            'mobile_phone' => 'MOBILE PHONE',
            'email' => 'EMAIL',
            'npwp' => 'NO NPWP',
            'information' => 'INFORMATION',
            'term_of_payment' => 'TERM OF PAYMENT',
            'is_active' => 'IS ACTIVE',
        ]);

        // sample data row to guide upload format
        $writer->addRow([
            'code' => 'S001',
            'name' => 'Supplier One',
            'contact_name' => 'John Doe',
            'address_1' => 'Jl. Example No. 123',
            'address_2' => 'Kel. Sample',
            'telephone' => '021-555-1234',
            'mobile_phone' => '081234567890',
            'email' => 'supplier@example.com',
            'npwp' => '12.345.678.9-012.345',
            'information' => 'Sample supplier for import template',
            'term_of_payment' => '30',
            'is_active' => 1,
        ]);

        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Supplier Template.xlsx');
    })->name('supplier.template');

    Volt::route('/bank-account', 'bank-account.index')->name('bank-account.index');
    Volt::route('/bank-account/create', 'bank-account.create')->name('bank-account.create');
    Volt::route('/bank-account/{bankAccount}/edit', 'bank-account.edit')->name('bank-account.edit');
    Volt::route('/bank-account/import', 'bank-account.import')->name('bank-account.import');

    Volt::route('/cash-account', 'cash-account.index')->name('cash-account.index');
    Volt::route('/cash-account/create', 'cash-account.create')->name('cash-account.create');
    Volt::route('/cash-account/{cashAccount}/edit', 'cash-account.edit')->name('cash-account.edit');
    Volt::route('/cash-account/import', 'cash-account.import')->name('cash-account.import');

    Volt::route('/cash-book', 'cash-book.index')->name('cash-book.index');
    Volt::route('/cash-book/create', 'cash-book.create')->name('cash-book.create');
    Volt::route('/cash-book/{cashBook}/edit', 'cash-book.edit')->name('cash-book.edit');
    Volt::route('/cash-book/import', 'cash-book.import')->name('cash-book.import');

    Volt::route('/cash-in', 'cash-in.index')->name('cash-in.index');
    Volt::route('/cash-in/create', 'cash-in.create')->name('cash-in.create');
    Volt::route('/cash-in/{cashIn}/edit', 'cash-in.edit')->name('cash-in.edit');
    Volt::route('/cash-in/import', 'cash-in.import')->name('cash-in.import');

    Volt::route('/cash-out', 'cash-out.index')->name('cash-out.index');
    Volt::route('/cash-out/create', 'cash-out.create')->name('cash-out.create');
    Volt::route('/cash-out/{cashOut}/edit', 'cash-out.edit')->name('cash-out.edit');
    Volt::route('/cash-out/import', 'cash-out.import')->name('cash-out.import');

    Volt::route('/bank-in', 'bank-in.index')->name('bank-in.index');
    Volt::route('/bank-in/create', 'bank-in.create')->name('bank-in.create');
    Volt::route('/bank-in/{bankIn}/edit', 'bank-in.edit')->name('bank-in.edit');
    Volt::route('/bank-in/import', 'bank-in.import')->name('bank-in.import');

    Volt::route('/bank-out', 'bank-out.index')->name('bank-out.index');
    Volt::route('/bank-out/create', 'bank-out.create')->name('bank-out.create');
    Volt::route('/bank-out/{bankOut}/edit', 'bank-out.edit')->name('bank-out.edit');
    Volt::route('/bank-out/import', 'bank-out.import')->name('bank-out.import');

    Volt::route('/intercash', 'intercash.index')->name('intercash.index');
    Volt::route('/intercash/create', 'intercash.create')->name('intercash.create');
    Volt::route('/intercash/{intercash}/edit', 'intercash.edit')->name('intercash.edit');

    Volt::route('/stock-adjustment-in', 'stock-adjustment-in.index')->name('stock-adjustment-in.index');
    Volt::route('/stock-adjustment-in/create', 'stock-adjustment-in.create')->name('stock-adjustment-in.create');
    Volt::route('/stock-adjustment-in/{stockAdjustmentIn}/edit', 'stock-adjustment-in.edit')->name('stock-adjustment-in.edit');

    Volt::route('/stock-adjustment-out', 'stock-adjustment-out.index')->name('stock-adjustment-out.index');
    Volt::route('/stock-adjustment-out/create', 'stock-adjustment-out.create')->name('stock-adjustment-out.create');
    Volt::route('/stock-adjustment-out/{stockAdjustmentOut}/edit', 'stock-adjustment-out.edit')->name('stock-adjustment-out.edit');

    Volt::route('/other-payable-invoice', 'other-payable-invoice.index')->name('other-payable-invoice.index');
    Volt::route('/other-payable-invoice/create', 'other-payable-invoice.create')->name('other-payable-invoice.create');
    Volt::route('/other-payable-invoice/{otherPayableInvoice}/edit', 'other-payable-invoice.edit')->name('other-payable-invoice.edit');
    Volt::route('/other-payable-invoice/import', 'other-payable-invoice.import')->name('other-payable-invoice.import');

    Volt::route('/other-payable-settlement', 'other-payable-settlement.index')->name('other-payable-settlement.index');
    Volt::route('/other-payable-settlement/create', 'other-payable-settlement.create')->name('other-payable-settlement.create');
    Volt::route('/other-payable-settlement/{otherPayableSettlement}/edit', 'other-payable-settlement.edit')->name('other-payable-settlement.edit');
    Volt::route('/other-payable-settlement/import', 'other-payable-settlement.import')->name('other-payable-settlement.import');

    Volt::route('/prepaid-account', 'prepaid-account.index')->name('prepaid-account.index');

    Volt::route('/recipe', 'recipe.index')->name('recipe.index');
    Volt::route('/recipe/create', 'recipe.create')->name('recipe.create');
    Volt::route('/recipe/{recipe}/edit', 'recipe.edit')->name('recipe.edit');

    Volt::route('/bom', 'bom.index')->name('bom.index');
    Volt::route('/bom/create', 'bom.create')->name('bom.create');
    Volt::route('/bom/{bom}', 'bom.show')->name('bom.show');
    Volt::route('/bom/{bom}/edit', 'bom.edit')->name('bom.edit');

    Volt::route('/production', 'production.index')->name('production.index');
    Volt::route('/production/create', 'production.create')->name('production.create');
    Volt::route('/production/{production}/edit', 'production.edit')->name('production.edit');

    Volt::route('/report/stock', 'report.stock')->name('report.stock');
    Volt::route('/report/stock-movement', 'report.stock-movement')->name('report.stock-movement');

    Volt::route('/request', 'request.index')->name('request.index');
    Volt::route('/request/create', 'request.create')->name('request.create');
    Volt::route('/request/{request}/edit', 'request.edit')->name('request.edit');

    Volt::route('/coa', 'coa.index')->name('coa.index');
    Volt::route('/coa/create', 'coa.create')->name('coa.create');
    Volt::route('/coa/{coa}/edit', 'coa.edit')->name('coa.edit');
    Volt::route('/coa/import', 'coa.import')->name('coa.import');

    Volt::route('/items-master', 'items-master.index')->name('items-master.index');
    Volt::route('/items-master/create', 'items-master.create')->name('items-master.create');
    Volt::route('/items-master/{serviceCharge}/edit', 'items-master.edit')->name('items-master.edit');
    Volt::route('/items-master/import', 'items-master.import')->name('items-master.import');
    Route::get('/items-master/template', function () {
        $writer = Spatie\SimpleExcel\SimpleExcelWriter::streamDownload('Items Master Template.xlsx');
        $writer->addRow([
            'id' => 'ID',
            'code' => 'CODE',
            'name' => 'NAME',
            'transport' => 'TRANSPORT',
            'type' => 'TYPE',
            'coa_buying' => 'COA BUYING CODE',
            'coa_selling' => 'COA SELLING CODE',
            'group' => 'GROUP CODE',
            'is_active' => 'IS ACTIVE',
        ]);

        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Items Master Template.xlsx');
    })->name('items-master.template');

    Volt::route('/items-master-group', 'items-master-group.index')->name('items-master-group.index');
    Volt::route('/items-master-group/create', 'items-master-group.create')->name('items-master-group.create');
    Volt::route('/items-master-group/{group}/edit', 'items-master-group.edit')->name('items-master-group.edit');

    Volt::route('/company', 'company.index')->name('company.index');
    Volt::route('/company/create', 'company.create')->name('company.create');
    Volt::route('/company/{company}/edit', 'company.create')->name('company.edit');

    Volt::route('/ppn', 'ppn.index')->name('ppn.index');
    Volt::route('/ppn/create', 'ppn.create')->name('ppn.create');
    Volt::route('/ppn/{ppn}/edit', 'ppn.edit')->name('ppn.edit');
    Volt::route('/pph', 'pph.index')->name('pph.index');
    Volt::route('/pph/create', 'pph.create')->name('pph.create');
    Volt::route('/pph/{pph}/edit', 'pph.edit')->name('pph.edit');

    Volt::route('/sales-order', 'sales-order.index')->name('sales-order.index');
    Volt::route('/sales-order/create', 'sales-order.create')->name('sales-order.create');
    Volt::route('/sales-order/{salesOrder}/edit', 'sales-order.edit')->name('sales-order.edit');
    Volt::route('/sales-order/import', 'sales-order.import')->name('sales-order.import');

    // User Management (Super Admin Only)
    Volt::route('/user-management', 'user-management')
        ->name('user-management')
        ->middleware(\Spatie\Permission\Middleware\RoleMiddleware::class . ':Super Admin');

    Volt::route('/order-label', 'order-label.index')->name('order-label.index');
    Volt::route('/dashboard', 'order-label.dashboard')->name('dashboard');
    Volt::route('/order-label/create', 'order-label.create')->name('order-label.create');
    Volt::route('/order-label/{orderLabel}/edit', 'order-label.edit')->name('order-label.edit');
    Volt::route('/order-label/import', 'order-label.import')->name('order-label.import');
    Route::get('/order-label/download/{path}', [\App\Http\Controllers\OrderLabelController::class, 'download'])
        ->where('path', '.*')
        ->name('order-label.download');
    Route::get('/order-label/download-all', [\App\Http\Controllers\OrderLabelController::class, 'downloadAll'])
        ->name('order-label.download-all');
    Route::get('/order-label/download-batch-zip', function() {
        $zipPath = session('batch_download_zip');
        $zipFileName = session('batch_download_filename');

        \Log::info('Download batch zip requested', [
            'zipPath' => $zipPath,
            'zipFileName' => $zipFileName,
            'fileExists' => $zipPath ? file_exists($zipPath) : false
        ]);

        if ($zipPath && file_exists($zipPath)) {
            session()->forget(['batch_download_zip', 'batch_download_filename']);
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        \Log::error('Batch zip download failed - file not found');
        abort(404, 'Download file not found or expired');
    })->name('order-label.download-batch-zip');
    Route::get('/order-label/{orderLabel}', [\App\Http\Controllers\OrderLabelController::class, 'show'])
        ->name('order-label.show');

    // Print Label routes
    Volt::route('/print-label', 'print-label.index')->name('print-label.index');
    Volt::route('/print-label/dashboard', 'print-label.dashboard')->name('print-label.dashboard');
    Volt::route('/print-label/create', 'print-label.create')->name('print-label.create');
    Volt::route('/print-label/{orderLabel}/edit', 'print-label.edit')->name('print-label.edit');
    Volt::route('/print-label/import', 'print-label.import')->name('print-label.import');
    Route::get('/print-label/download/{path}', [\App\Http\Controllers\PrintLabelController::class, 'download'])
        ->where('path', '.*')
        ->name('print-label.download');
    Route::get('/print-label/download-all', [\App\Http\Controllers\PrintLabelController::class, 'downloadAll'])
        ->name('print-label.download-all');
    Route::get('/print-label/download-batch-zip', function() {
        $zipPath = session('batch_download_zip');
        $zipFileName = session('batch_download_filename');

        \Log::info('Download batch zip requested', [
            'zipPath' => $zipPath,
            'zipFileName' => $zipFileName,
            'fileExists' => $zipPath ? file_exists($zipPath) : false
        ]);

        if ($zipPath && file_exists($zipPath)) {
            session()->forget(['batch_download_zip', 'batch_download_filename']);
            return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        \Log::error('Batch zip download failed - file not found');
        abort(404, 'Download file not found or expired');
    })->name('print-label.download-batch-zip');
    Route::get('/print-label/{orderLabel}', [\App\Http\Controllers\PrintLabelController::class, 'show'])
        ->name('print-label.show');

    Volt::route('/delivery-order', 'delivery-order.index')->name('delivery-order.index');
    Volt::route('/delivery-order/create', 'delivery-order.create')->name('delivery-order.create');
    Volt::route('/delivery-order/{deliveryOrder}/edit', 'delivery-order.edit')->name('delivery-order.edit');

    Volt::route('/sales-invoice', 'sales-invoice.index')->name('sales-invoice.index');
    Volt::route('/sales-invoice/create', 'sales-invoice.create')->name('sales-invoice.create');
    Volt::route('/sales-invoice/{salesInvoice}/edit', 'sales-invoice.edit')->name('sales-invoice.edit');
    Volt::route('/sales-invoice/import', 'sales-invoice.import')->name('sales-invoice.import');

    Volt::route('/sales-invoice-direct', 'sales-invoice-direct.index')->name('sales-invoice-direct.index');
    Volt::route('/sales-invoice-direct/create', 'sales-invoice-direct.create')->name('sales-invoice-direct.create');
    Volt::route('/sales-invoice-direct/{salesInvoiceDirect}/edit', 'sales-invoice-direct.edit')->name('sales-invoice-direct.edit');
    Volt::route('/sales-invoice-direct/import', 'sales-invoice-direct.import')->name('sales-invoice-direct.import');

    // AR Outstanding -> added as requested
    Volt::route('/sales/ar-outstanding', 'sales.ar-outstanding')->name('sales.ar-outstanding');

    // Purchase module placeholders
    Volt::route('/purchase-order', 'purchase-order.index')->name('purchase-order.index');
    Volt::route('/purchase-order/create', 'purchase-order.create')->name('purchase-order.create');
    Volt::route('/purchase-order/{purchaseOrder}/edit', 'purchase-order.edit')->name('purchase-order.edit');
    Volt::route('/purchase-order/import', 'purchase-order.import')->name('purchase-order.import');
    Route::get('/purchase-order/template', function () {
        $writer = Spatie\SimpleExcel\SimpleExcelWriter::streamDownload('Purchase Order Template.xlsx');
        $writer->addRow([
            'code' => 'CODE',
            'order_date' => 'ORDER DATE',
            'due_date' => 'DUE DATE',
            'supplier' => 'SUPPLIER',
            'note' => 'NOTE',
            'is_active' => 'IS ACTIVE',
        ]);

        return response()->streamDownload(function() use ($writer){
            $writer->close();
        }, 'Purchase Order Template.xlsx');
    })->name('purchase-order.template');

    // Purchase Receival
    Volt::route('/purchase-receival', 'purchase-receival.index')->name('purchase-receival.index');
    Volt::route('/purchase-receival/create', 'purchase-receival.create')->name('purchase-receival.create');
    Volt::route('/purchase-receival/{purchaseReceival}/edit', 'purchase-receival.edit')->name('purchase-receival.edit');

    Volt::route('/purchase-invoice', 'purchase-invoice.index')->name('purchase-invoice.index');
    Volt::route('/purchase-invoice/create', 'purchase-invoice.create')->name('purchase-invoice.create');
    Volt::route('/purchase-invoice/{purchaseInvoice}/edit', 'purchase-invoice.edit')->name('purchase-invoice.edit');
    Volt::route('/purchase-invoice/import', 'purchase-invoice.import')->name('purchase-invoice.import');

    // Purchase Invoice Direct
    Volt::route('/purchase-invoice-direct', 'purchase-invoice-direct.index')->name('purchase-invoice-direct.index');
    Volt::route('/purchase-invoice-direct/create', 'purchase-invoice-direct.create')->name('purchase-invoice-direct.create');
    Volt::route('/purchase-invoice-direct/{purchaseInvoice}/edit', 'purchase-invoice-direct.edit')->name('purchase-invoice-direct.edit');
    Volt::route('/purchase-invoice-direct/import', 'purchase-invoice-direct.import')->name('purchase-invoice-direct.import');

    Volt::route('/purchase-settlement', 'purchase-settlement.index')->name('purchase-settlement.index');
    Volt::route('/purchase-settlement/create', 'purchase-settlement.create')->name('purchase-settlement.create');
    Volt::route('/purchase-settlement/{purchaseSettlement}/edit', 'purchase-settlement.edit')->name('purchase-settlement.edit');
    Volt::route('/purchase-settlement/import', 'purchase-settlement.import')->name('purchase-settlement.import');

    Volt::route('/purchase/ap-outstanding', 'purchase.ap-outstanding')->name('purchase.ap-outstanding');

    Volt::route('/sales-settlement', 'sales-settlement.index')->name('sales-settlement.index');
    Volt::route('/sales-settlement/create', 'sales-settlement.create')->name('sales-settlement.create');
    Volt::route('/sales-settlement/{salesSettlement}/edit', 'sales-settlement.edit')->name('sales-settlement.edit');
    Volt::route('/sales-settlement/import', 'sales-settlement.import')->name('sales-settlement.import');

    Volt::route('/journal', 'journal.index')->name('journal.index');
    Volt::route('/journal/create', 'journal.create')->name('journal.create');
    Volt::route('/journal/{journal}/edit', 'journal.edit')->name('journal.edit');
    Volt::route('/journal/import', 'journal.import')->name('journal.import');

    Volt::route('/opening-balance', 'opening-balance.index')->name('opening-balance.index');
    Volt::route('/opening-balance/import', 'opening-balance.import')->name('opening-balance.import');

    Volt::route('/report/general-ledger', 'report.general-ledger')->name('report.general-ledger');
    Volt::route('/report/trial-balance', 'report.trial-balance')->name('report.trial-balance');
    Volt::route('/report/balance-sheet', 'report.balance-sheet')->name('report.balance-sheet');
    Volt::route('/report/profit-loss', 'report.profit-loss')->name('report.profit-loss');
    Volt::route('/report/sales-order', 'report.sales-order')->name('report.sales-order');
    Volt::route('/report/delivery-order', 'report.delivery-order')->name('report.delivery-order');
    Volt::route('/report/sales-invoice', 'report.sales-invoice')->name('report.sales-invoice');
    Volt::route('/report/purchase-order', 'report.purchase-order')->name('report.purchase-order');
    Volt::route('/report/purchase-receival', 'report.purchase-receival')->name('report.purchase-receival');
    Volt::route('/report/purchase-invoice', 'report.purchase-invoice')->name('report.purchase-invoice');

    Volt::route('/users', 'users.index')->name('users.index');
    Volt::route('/users/create', 'users.create')->name('users.create');
    Volt::route('/users/{user}/edit', 'users.edit')->name('users.edit');
    Volt::route('/users/profile', 'users.profile')->name('users.profile');
    Volt::route('/permissions', 'permissions.index')->name('permissions.index');
    Volt::route('/permissions/create', 'permissions.create')->name('permissions.create');
    Volt::route('/permissions/{permission}/edit', 'permissions.edit')->name('permissions.edit');
    Volt::route('/permissions/import', 'permissions.import')->name('permissions.import');
    Volt::route('/roles', 'roles.index')->name('roles.index');
    Volt::route('/roles/create', 'roles.create')->name('roles.create');
    Volt::route('/roles/{role}/edit', 'roles.edit')->name('roles.edit');
    Volt::route('/roles/import', 'roles.import')->name('roles.import');
    Volt::route('/send-test-mail', 'mail.test')->name('mail.test');

    Volt::route('/user-logs', 'user-logs.index')->name('user-logs.index');
    Volt::route('/user-logs/create', 'user-logs.create')->name('user-logs.create');
    Volt::route('/user-logs/{userLog}/edit', 'user-logs.edit')->name('user-logs.edit');

    Volt::route('/settings/general', 'setting.general')->name('setting.general');
    Volt::route('/settings/account-mapping', 'setting.account-mapping')->name('setting.account-mapping');
    Volt::route('/settings/code', 'setting.code')->name('setting.code');
    Volt::route('/settings/draft', 'setting.draft')->name('setting.draft');
    Volt::route('/queue-log', 'queue-log')->name('queue-log');

    // print
    Route::get('/print/cash-in/{cashIn}', [\App\Http\Controllers\CashInPrintController::class, 'show'])->name('print.cash-in');
    Route::get('/print/cash-out/{cashOut}', [\App\Http\Controllers\CashOutPrintController::class, 'show'])->name('print.cash-out');
    Route::get('/print/journal/{resource}/{id}', [PrintController::class, 'journal'])->name('print.journal');
    Route::get('/print/bank-in/{bankIn}', [\App\Http\Controllers\BankInPrintController::class, 'show'])->name('print.bank-in');
    Route::get('/print/bank-out/{bankOut}', [\App\Http\Controllers\BankOutPrintController::class, 'show'])->name('print.bank-out');
    Route::get('/print/other-payable-settlement/{otherPayableSettlement}', [\App\Http\Controllers\OtherPayableSettlementPrintController::class, 'show'])->name('print.other-payable-settlement');
    Route::get('/print/purchase-order/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderPrintController::class, 'show'])->name('print.purchase-order');
    Route::get('/print/purchase-receival/{purchaseReceival}', [\App\Http\Controllers\PurchaseReceivalPrintController::class, 'show'])->name('print.purchase-receival');
    Route::get('/print/purchase-invoice/{purchaseInvoice}', [\App\Http\Controllers\PurchaseInvoicePrintController::class, 'show'])->name('print.purchase-invoice');
    Route::get('/print/purchase-invoice-direct/{purchaseInvoice}', [\App\Http\Controllers\PurchaseInvoicePrintController::class, 'show'])->name('print.purchase-invoice-direct');
    Route::get('/print/delivery-order/{deliveryOrder}', [\App\Http\Controllers\DeliveryOrderPrintController::class, 'show'])->name('print.delivery-order');
    Route::get('/print/sales-order/{salesOrder}', [\App\Http\Controllers\SalesOrderPrintController::class, 'show'])->name('print.sales-order');
    Route::get('/print/order-label/{orderLabel}', [\App\Http\Controllers\OrderLabelPrintController::class, 'show'])->name('print.order-label');
    Route::get('/print/sales-invoice/{salesInvoice}', [\App\Http\Controllers\SalesInvoicePrintController::class, 'show'])->name('print.sales-invoice');
    Route::get('/print/sales-invoice-direct/{salesInvoiceDirect}', [\App\Http\Controllers\SalesInvoiceDirectPrintController::class, 'show'])->name('print.sales-invoice-direct');
    Route::get('/print/sales-settlement/{salesSettlement}', [\App\Http\Controllers\SalesSettlementPrintController::class, 'show'])->name('print.sales-settlement');
    Route::get('/print/purchase-settlement/{purchaseSettlement}', [\App\Http\Controllers\PurchaseSettlementPrintController::class, 'show'])->name('print.purchase-settlement');
    Route::get('/print/other-payable-invoice/{otherPayableInvoice}', [\App\Http\Controllers\OtherPayableInvoicePrintController::class, 'show'])->name('print.other-payable-invoice');
    Route::get('/print/recipe/{recipe}', [\App\Http\Controllers\RecipePrintController::class, 'show'])->name('print.recipe');
    Route::get('/print/bom/{bom}', [\App\Http\Controllers\BOMPrintController::class, 'show'])->name('print.bom');
    Route::get('/print/production/{production}', [\App\Http\Controllers\ProductionPrintController::class, 'show'])->name('print.production');

    Route::get('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('users.logout');
});
