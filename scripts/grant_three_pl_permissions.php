<?php

use Spatie\Permission\Models\Role;

$role = Role::where('name', 'admin')->first();

if ($role) {
    $role->givePermissionTo([
        'view three-pl',
        'create three-pl',
        'update three-pl',
        'delete three-pl',
        'export three-pl',
        'import three-pl'
    ]);
    echo "Permissions granted to admin role\n";
} else {
    echo "Admin role not found\n";
}
