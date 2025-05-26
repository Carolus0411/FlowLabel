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

    Volt::route('/settings', 'setting.general')->name('setting.general');

    Route::get('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('users.logout');
});
