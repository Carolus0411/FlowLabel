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

    Volt::route('/bank-name', 'bank.index')->name('bank.index');
    Volt::route('/bank-name/create', 'bank.create')->name('bank.create');
    Volt::route('/bank-name/{bank}/edit', 'bank.edit')->name('bank.edit');
    Volt::route('/bank-name/import', 'bank.import')->name('bank.import');

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

    Volt::route('/prepaid-account', 'prepaid-account.index')->name('prepaid-account.index');

    Volt::route('/request', 'request.index')->name('request.index');
    Volt::route('/request/create', 'request.create')->name('request.create');
    Volt::route('/request/{request}/edit', 'request.edit')->name('request.edit');

    Volt::route('/coa', 'coa.index')->name('coa.index');
    Volt::route('/coa/create', 'coa.create')->name('coa.create');
    Volt::route('/coa/{coa}/edit', 'coa.edit')->name('coa.edit');
    Volt::route('/coa/import', 'coa.import')->name('coa.import');

    Volt::route('/service-charge', 'service-charge.index')->name('service-charge.index');
    Volt::route('/service-charge/create', 'service-charge.create')->name('service-charge.create');
    Volt::route('/service-charge/{serviceCharge}/edit', 'service-charge.edit')->name('service-charge.edit');
    Volt::route('/service-charge/import', 'service-charge.import')->name('service-charge.import');

    Volt::route('/ppn', 'ppn.index')->name('ppn.index');
    Volt::route('/ppn/create', 'ppn.create')->name('ppn.create');
    Volt::route('/ppn/{ppn}/edit', 'ppn.edit')->name('ppn.edit');
    Volt::route('/pph', 'pph.index')->name('pph.index');
    Volt::route('/pph/create', 'pph.create')->name('pph.create');
    Volt::route('/pph/{pph}/edit', 'pph.edit')->name('pph.edit');

    Volt::route('/sales-invoice', 'sales-invoice.index')->name('sales-invoice.index');
    Volt::route('/sales-invoice/create', 'sales-invoice.create')->name('sales-invoice.create');
    Volt::route('/sales-invoice/{salesInvoice}/edit', 'sales-invoice.edit')->name('sales-invoice.edit');
    Volt::route('/sales-invoice/import', 'sales-invoice.import')->name('sales-invoice.import');

    // AR Outstanding -> added as requested
    Volt::route('/sales/ar-outstanding', 'sales.ar-outstanding')->name('sales.ar-outstanding');

    // Purchase module placeholders
    Volt::route('/purchase-invoice', 'purchase-invoice.index')->name('purchase-invoice.index');
    Volt::route('/purchase-invoice/create', 'purchase-invoice.create')->name('purchase-invoice.create');
    Volt::route('/purchase-invoice/{purchaseInvoice}/edit', 'purchase-invoice.edit')->name('purchase-invoice.edit');
    Volt::route('/purchase-invoice/import', 'purchase-invoice.import')->name('purchase-invoice.import');

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

    // print
    Route::get('/print/cash-in/{cashIn}', [PrintController::class, 'cashIn'])->name('print.cash-in');
    Route::get('/print/journal/{resource}/{id}', [PrintController::class, 'journal'])->name('print.journal');
    Route::get('/print/bank-in/{bankIn}', [PrintController::class, 'bankIn'])->name('print.bank-in');
    Route::get('/print/bank-out/{bankOut}', [PrintController::class, 'bankOut'])->name('print.bank-out');

    Route::get('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('users.logout');
});
