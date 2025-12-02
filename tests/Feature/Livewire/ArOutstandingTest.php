<?php

use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\UserSeeder;
use App\Models\User;
use App\Models\Contact;
use App\Models\SalesInvoice;

uses(RefreshDatabase::class);

test('customer filter in ar outstanding filters invoices correctly', function() {
    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $c1 = Contact::create(['code' => 'C001', 'name' => 'Alpha', 'is_active' => true]);
    $c2 = Contact::create(['code' => 'C002', 'name' => 'Beta', 'is_active' => true]);

    $inv1 = SalesInvoice::create([
        'code' => 'INV-001', 'invoice_date' => now()->subDays(1)->format('Y-m-d'), 'contact_id' => $c1->id, 'invoice_amount' => 100, 'balance_amount' => 100, 'payment_status' => 'unpaid', 'saved' => 1
    ]);

    $inv2 = SalesInvoice::create([
        'code' => 'INV-002', 'invoice_date' => now()->subDays(1)->format('Y-m-d'), 'contact_id' => $c2->id, 'invoice_amount' => 50, 'balance_amount' => 50, 'payment_status' => 'unpaid', 'saved' => 1
    ]);

    Volt::actingAs($user, 'web')
        ->test('sales.ar-outstanding')
        ->set('contact_id', $c1->id)
        ->call('search')
        ->assertSee('INV-001')
        ->assertDontSee('INV-002');
});
