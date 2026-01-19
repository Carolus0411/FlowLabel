<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GrantLabelOrderPermission extends Command
{
    protected $signature = 'permission:grant-label-order';
    protected $description = 'Grant label-order permissions to first user';

    public function handle()
    {
        $user = User::first();

        if ($user) {
            $user->givePermissionTo(['view label-order', 'create label-order']);
            $this->info("Permissions granted to user: {$user->name}");
        } else {
            $this->error("No users found");
        }
    }
}
