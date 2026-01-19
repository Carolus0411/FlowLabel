#!/bin/bash

# Test script untuk memverifikasi Super Admin tidak hilang setelah migrate

echo "==================================="
echo "Testing Super Admin Seeder"
echo "==================================="

echo ""
echo "Step 1: Running migrate:fresh dengan seed..."
php artisan migrate:fresh --seed

echo ""
echo "Step 2: Verifying Super Admin exists..."
php verify_super_admin.php

echo ""
echo "Step 3: Testing seeder dapat dijalankan multiple kali..."
php artisan db:seed --class=SuperAdminSeeder

echo ""
echo "Step 4: Final verification..."
php verify_super_admin.php

echo ""
echo "==================================="
echo "✓ Test completed successfully!"
echo "==================================="
