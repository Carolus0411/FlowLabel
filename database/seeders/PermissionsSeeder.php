<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('permissions')->truncate();

        DB::table('permissions')->insert([
            // Users permissions
            [
  'id' => 1,
  'name' => 'view users',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'users',
],
            [
  'id' => 2,
  'name' => 'create users',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'users',
],
            [
  'id' => 3,
  'name' => 'update users',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'users',
],
            [
  'id' => 4,
  'name' => 'delete users',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'users',
],
            // Roles permissions
            [
  'id' => 5,
  'name' => 'view roles',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'roles',
],
            [
  'id' => 6,
  'name' => 'create roles',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'roles',
],
            [
  'id' => 7,
  'name' => 'update roles',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'roles',
],
            [
  'id' => 8,
  'name' => 'delete roles',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'roles',
],
            // Permissions permissions
            [
  'id' => 9,
  'name' => 'view permissions',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'permissions',
],
            [
  'id' => 10,
  'name' => 'create permissions',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'permissions',
],
            [
  'id' => 11,
  'name' => 'update permissions',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'permissions',
],
            [
  'id' => 12,
  'name' => 'delete permissions',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'permissions',
],
            [
  'id' => 13,
  'name' => 'import permissions',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'permissions',
],
            [
  'id' => 14,
  'name' => 'export permissions',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'permissions',
],
            // User Logs permission
            [
  'id' => 15,
  'name' => 'view user logs',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'user-logs',
],
            // Order Label permissions
            [
  'id' => 16,
  'name' => 'view order-label',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'order-label',
],
            [
  'id' => 17,
  'name' => 'create order-label',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'order-label',
],
            [
  'id' => 18,
  'name' => 'update order-label',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'order-label',
],
            [
  'id' => 19,
  'name' => 'delete order-label',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'order-label',
],
            [
  'id' => 20,
  'name' => 'import order-label',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'order-label',
],
            [
  'id' => 21,
  'name' => 'export order-label',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'order-label',
],
            // Settings permissions
            [
  'id' => 22,
  'name' => 'view general-setting',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'general-setting',
],
            [
  'id' => 23,
  'name' => 'update general-setting',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'general-setting',
],
            [
  'id' => 24,
  'name' => 'view account-mapping',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'account-mapping',
],
            [
  'id' => 25,
  'name' => 'update account-mapping',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'account-mapping',
],
            [
  'id' => 26,
  'name' => 'view setting-code',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'setting-code',
],
            [
  'id' => 27,
  'name' => 'update setting-code',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'setting-code',
],
            [
  'id' => 28,
  'name' => 'view draft',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'draft',
],
            [
  'id' => 29,
  'name' => 'send test-mail',
  'guard_name' => 'web',
  'created_at' => '2025-12-03 09:43:44',
  'updated_at' => '2025-12-03 09:43:44',
  'resource' => 'test-mail',
],
            // 3PL permissions
            [
  'id' => 30,
  'name' => 'view three-pl',
  'guard_name' => 'web',
  'created_at' => '2026-01-19 09:23:33',
  'updated_at' => '2026-01-19 09:23:33',
  'resource' => 'three-pl',
],
            [
  'id' => 31,
  'name' => 'create three-pl',
  'guard_name' => 'web',
  'created_at' => '2026-01-19 09:23:33',
  'updated_at' => '2026-01-19 09:23:33',
  'resource' => 'three-pl',
],
            [
  'id' => 32,
  'name' => 'update three-pl',
  'guard_name' => 'web',
  'created_at' => '2026-01-19 09:23:33',
  'updated_at' => '2026-01-19 09:23:33',
  'resource' => 'three-pl',
],
            [
  'id' => 33,
  'name' => 'delete three-pl',
  'guard_name' => 'web',
  'created_at' => '2026-01-19 09:23:33',
  'updated_at' => '2026-01-19 09:23:33',
  'resource' => 'three-pl',
],
            [
  'id' => 34,
  'name' => 'export three-pl',
  'guard_name' => 'web',
  'created_at' => '2026-01-19 09:23:33',
  'updated_at' => '2026-01-19 09:23:33',
  'resource' => 'three-pl',
],
            [
  'id' => 35,
  'name' => 'import three-pl',
  'guard_name' => 'web',
  'created_at' => '2026-01-19 09:23:33',
  'updated_at' => '2026-01-19 09:23:33',
  'resource' => 'three-pl',
],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
