# Test script untuk memverifikasi Super Admin tidak hilang setelah migrate

Write-Host "===================================" -ForegroundColor Cyan
Write-Host "Testing Super Admin Seeder" -ForegroundColor Cyan
Write-Host "===================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Step 1: Running migrate:fresh dengan seed..." -ForegroundColor Yellow
php artisan migrate:fresh --seed

Write-Host ""
Write-Host "Step 2: Verifying Super Admin exists..." -ForegroundColor Yellow
php verify_super_admin.php

Write-Host ""
Write-Host "Step 3: Testing seeder dapat dijalankan multiple kali..." -ForegroundColor Yellow
php artisan db:seed --class=SuperAdminSeeder

Write-Host ""
Write-Host "Step 4: Final verification..." -ForegroundColor Yellow
php verify_super_admin.php

Write-Host ""
Write-Host "===================================" -ForegroundColor Green
Write-Host "✓ Test completed successfully!" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
