<?php

use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Supplier;

uses(RefreshDatabase::class);

test('guest cannot access supplier', function () {

    $response = $this->get('/cp/supplier');
    $response->assertStatus(302);
});

test('super admin can access supplier', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $response = $this->actingAs($user, 'web')
        ->get('/cp/supplier')
        ->assertStatus(200);
});

test('super admin can create supplier', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    Volt::actingAs($user, 'web')
        ->test('supplier.create')
        ->set('code', 'S001')
        ->set('name', 'Supplier One')
        ->call('save');

    $supplier = Supplier::firstWhere('name','Supplier One');
    $this->assertModelExists($supplier);
});

test('super admin can edit supplier', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $supplier = Supplier::create([
        'code' => 'S001',
        'name' => 'Supplier One',
        'is_active' => true,
    ]);

    Volt::actingAs($user, 'web')
        ->test('supplier.edit', ['supplier' => $supplier])
        ->set('code', 'S001')
        ->set('name', 'Supplier Two')
        ->call('save');

    $supplier = Supplier::firstWhere('name','Supplier Two');
    $this->assertModelExists($supplier);
});

test('super admin can access supplier import', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $response = $this->actingAs($user, 'web')
        ->get('/cp/supplier/import')
        ->assertStatus(200)
        ->assertDontSee('Download Template')
        ->assertSee('Template can be created by exporting data');
});

// Download template test removed: download button removed from import page; Use Export from index instead.

test('super admin can delete supplier', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $supplier = Supplier::create([
        'id' => '1',
        'code' => 'S001',
        'name' => 'Supplier One',
        'is_active' => true,
    ]);

    Volt::actingAs($user, 'web')
        ->test('supplier.index')
        ->call('delete', supplier: $supplier);

    $this->assertModelMissing($supplier);
});
