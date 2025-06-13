<?php

use Livewire\Volt\Volt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Contact;

uses(RefreshDatabase::class);

test('guest cannot access contact', function () {

    $response = $this->get('/cp/contact');
    $response->assertStatus(302);
});

test('super admin can access contact', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $response = $this->actingAs($user, 'web')
        ->get('/cp/contact')
        ->assertStatus(200);
});

test('super admin can create contact', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    Volt::actingAs($user, 'web')
        ->test('contact.create')
        ->set('code', 'A001')
        ->set('name', 'Alex Kidd')
        ->call('save');

    $contact = Contact::firstWhere('name','Alex Kidd');
    $this->assertModelExists($contact);
});

test('super admin can edit contact', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $contact = Contact::create([
        'code' => 'A001',
        'name' => 'Alex Kidd',
        'is_active' => true,
    ]);

    Volt::actingAs($user, 'web')
        ->test('contact.edit', ['contact' => $contact])
        ->set('code', 'A001')
        ->set('name', 'Sonya')
        ->call('save');

    $contact = Contact::firstWhere('name','Sonya');
    $this->assertModelExists($contact);
});

test('super admin can delete contact', function () {

    $this->seed(UserSeeder::class);
    $user = User::find(1);

    $contact = Contact::create([
        'id' => '1',
        'code' => 'A001',
        'name' => 'Alex Kidd',
        'is_active' => true,
    ]);

    Volt::actingAs($user, 'web')
        ->test('contact.index')
        ->call('delete', contact: $contact);

    $this->assertModelMissing($contact);
});
