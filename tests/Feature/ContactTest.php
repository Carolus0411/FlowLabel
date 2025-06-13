<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\UserSeeder;
use App\Models\User;

uses(RefreshDatabase::class);

test('guest cannot access contact', function () {

    $response = $this->get('/');
    $response->assertStatus(302);
});

test('super admin can access contact', function () {

    $this->seed(UserSeeder::class);

    $user = User::find(1);
    $response = $this->actingAs($user, 'web')->get('/contact');
    $response->assertStatus(200);
});
