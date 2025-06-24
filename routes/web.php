<?php

use Illuminate\Http\Request;
use Livewire\Volt\Volt;
use App\Livewire\Home;

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

    Volt::route('/journal', 'journal.index')->name('journal.index');
    Volt::route('/journal/create', 'journal.create')->name('journal.create');
    Volt::route('/journal/{journal}/edit', 'journal.edit')->name('journal.edit');
    Volt::route('/journal/import', 'journal.import')->name('journal.import');
    Volt::route('/opening-balance', 'opening-balance.index')->name('opening-balance.index');

    Volt::route('/report/general-ledger', 'report.general-ledger')->name('report.general-ledger');

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

    Route::get('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('users.logout');
});
