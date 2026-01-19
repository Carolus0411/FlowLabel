# Super Admin Seeder - Documentation

## Overview

Sistem Super Admin yang otomatis dibuat saat database seeding. User Super Admin tidak akan hilang saat melakukan migrate atau migrate:fresh dengan seed.

## Credentials

```
Email: superadmin@labsysflow.com
Password: password
```

## Cara Kerja

### 1. Otomatis Seeding

SuperAdminSeeder telah ditambahkan ke `DatabaseSeeder.php` sehingga akan otomatis dijalankan setiap kali:

```bash
php artisan db:seed
php artisan migrate:fresh --seed
php artisan migrate --seed
```

### 2. Idempotent (Aman dijalankan berkali-kali)

Seeder menggunakan `updateOrCreate()` dan `firstOrCreate()` sehingga:

- ✅ Jika user belum ada → akan dibuat
- ✅ Jika user sudah ada → akan diupdate (password tetap 'password')
- ✅ Tidak akan error duplicate entry
- ✅ Aman dijalankan berkali-kali

### 3. Role & Permissions

Seeder secara otomatis:

- Membuat role "Super Admin" jika belum ada
- Membuat 29 permissions dasar jika belum ada
- Assign semua permissions ke role Super Admin
- Assign role Super Admin ke user

## Struktur Permissions (29 total)

### User Management (4)

- view users
- create users
- update users
- delete users

### Role Management (5)

- view roles
- create roles
- update roles
- delete roles
- assign roles

### Permission Management (4)

- view permissions
- create permissions
- update permissions
- delete permissions

### Order Label (7)

- view order-label
- create order-label
- update order-label
- delete order-label
- import order-label
- export order-label
- print order-label

### 3PL Management (4)

- view three-pl
- create three-pl
- update three-pl
- delete three-pl

### Settings (2)

- view general-setting
- update general-setting

### User Logs (1)

- view user logs

### System (2)

- access all features
- manage system

## Files Modified

### 1. database/seeders/DatabaseSeeder.php

```php
$this->call([
    UsersSeeder::class,
    RolesSeeder::class,
    PermissionsSeeder::class,
    ModelHasRolesSeeder::class,
    SuperAdminSeeder::class,  // ← Ditambahkan di sini
    CompaniesSeeder::class,
    SettingsSeeder::class,
]);
```

### 2. database/seeders/SuperAdminSeeder.php

- Menggunakan `updateOrCreate()` untuk user
- Menggunakan `firstOrCreate()` untuk role & permissions
- Otomatis sync semua permissions ke Super Admin role
- Check role assignment sebelum assign (avoid duplicate)

### 3. database/seeders/UsersSeeder.php

- Diubah dari `truncate()` ke `firstOrCreate()`
- Menghilangkan `SET FOREIGN_KEY_CHECKS` (tidak kompatibel PostgreSQL)
- Menggunakan Eloquent model untuk insert

## Usage

### Fresh Install

```bash
php artisan migrate:fresh --seed
```

Super Admin akan otomatis dibuat dengan 29 permissions.

### Update Database

```bash
php artisan migrate
php artisan db:seed
```

Super Admin akan tetap ada atau dibuat jika belum ada.

### Manual Run

```bash
php artisan db:seed --class=SuperAdminSeeder
```

Bisa dijalankan kapan saja tanpa error.

### Verify Super Admin

```bash
php verify_super_admin.php
```

Script untuk verifikasi Super Admin exists dengan role dan permissions.

### Test Persistence

```bash
# Windows PowerShell
.\test_super_admin_persistence.ps1

# Linux/Mac
./test_super_admin_persistence.sh
```

Test lengkap untuk memastikan Super Admin persist setelah migrate:fresh.

## Login Super Admin

1. Buka aplikasi di browser
2. Login dengan:
    - Email: `superadmin@labsysflow.com`
    - Password: `password`
3. Navigate ke **Users > User Management**
4. Kelola user dan assign roles

## Features Super Admin

### User Management Interface

- View semua users dengan pagination
- Search users by name atau email
- Create user baru dengan multiple roles
- Edit user dan update roles
- Delete users (kecuali diri sendiri)
- Set status active/inactive

### Super Admin Capabilities

- Full access ke semua permissions
- Dapat assign role apapun ke user
- Akses ke User Management page (hanya Super Admin)
- Manage system settings

## Security

### Route Protection

```php
Route::middleware('role:Super Admin')->group(function () {
    Volt::route('/user-management', 'user-management')
        ->name('user-management');
});
```

### Component Protection

```php
public function mount()
{
    if (!auth()->user()->hasRole('Super Admin')) {
        abort(403, 'Unauthorized action.');
    }
}
```

### User Protection

- User tidak bisa delete diri sendiri
- Password selalu di-hash dengan bcrypt
- Status menggunakan Enum untuk type safety

## Troubleshooting

### Super Admin tidak ada setelah migrate

```bash
# Jalankan seeder manual
php artisan db:seed --class=SuperAdminSeeder

# Verify
php verify_super_admin.php
```

### Permission tidak lengkap

```bash
# Re-run seeder untuk sync permissions
php artisan db:seed --class=SuperAdminSeeder
```

### Error saat login

- Pastikan password: `password`
- Pastikan email: `superadmin@labsysflow.com`
- Check status user: harus `active`

### Menu tidak muncul

- Pastikan user memiliki role "Super Admin"
- Clear cache: `php artisan cache:clear`
- Check sidebar menu visibility logic

## Best Practices

1. **Jangan ubah email Super Admin** - Seeder depend on email sebagai unique identifier
2. **Backup sebelum migrate:fresh** - Selalu backup data penting
3. **Gunakan environment variables** - Untuk production, gunakan env untuk password
4. **Regular permission audit** - Review permissions secara berkala
5. **Test setelah deploy** - Selalu verify Super Admin exists setelah deployment

## Production Considerations

### Change Default Password

Edit `SuperAdminSeeder.php`:

```php
'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
```

Tambahkan di `.env`:

```
SUPER_ADMIN_PASSWORD=YourStrongPassword123!
```

### Email Notification

Tambahkan email notification saat Super Admin dibuat atau diupdate.

### Audit Log

Implement audit logging untuk tracking Super Admin activities.

## Related Files

- `database/seeders/SuperAdminSeeder.php` - Seeder utama
- `database/seeders/DatabaseSeeder.php` - Orchestrator
- `resources/views/livewire/user-management.blade.php` - UI Interface
- `routes/web.php` - Route definitions
- `resources/views/components/layouts/app.blade.php` - Menu sidebar
- `verify_super_admin.php` - Verification script
- `test_super_admin_persistence.ps1` - Test script

## Support

Jika ada masalah:

1. Check error log: `storage/logs/laravel.log`
2. Run verification: `php verify_super_admin.php`
3. Check database manually: `psql` atau `pgAdmin`
4. Re-run seeder: `php artisan db:seed --class=SuperAdminSeeder`
