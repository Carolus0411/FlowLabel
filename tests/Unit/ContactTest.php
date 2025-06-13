<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Database\Seeders\UserSeeder;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;
use App\Models\User;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function renders_successfully()
    {
        $this->seed(UserSeeder::class);

        $this->assertDatabaseCount('users', 1);

        // $user = User::find(1);
        // $this->actingAs($user)->get('/contact')
        //     ->assertSeeVolt('contact.index');
    }
}
