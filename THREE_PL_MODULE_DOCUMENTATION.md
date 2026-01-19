# Modul 3PL (Third Party Logistics) - CRUD

## Deskripsi
Modul master 3PL untuk mencatat data provider logistics pihak ketiga dengan informasi Kode 3PL dan Nama 3PL.

## Struktur Database

### Tabel: `three_pls`
- `id` - Primary key
- `code` - Kode 3PL (unique, required)
- `name` - Nama 3PL (required)
- `is_active` - Status aktif (boolean, default: true)
- `timestamps` - created_at, updated_at

## File yang Dibuat/Dimodifikasi

### 1. Migration
- `database/migrations/2026_01_19_092333_create_three_pls_table.php`

### 2. Model
- `app/Models/ThreePl.php`

### 3. Views (Livewire Components)
- `resources/views/livewire/three-pl/index.blade.php` - List & Search
- `resources/views/livewire/three-pl/create.blade.php` - Create Form
- `resources/views/livewire/three-pl/edit.blade.php` - Edit Form
- `resources/views/livewire/three-pl/import.blade.php` - Import Excel

### 4. Routes
Routes sudah tersedia di `routes/web.php`:
- `GET /cp/three-pl` - Index (three-pl.index)
- `GET /cp/three-pl/create` - Create (three-pl.create)
- `GET /cp/three-pl/{threePl}/edit` - Edit (three-pl.edit)
- `GET /cp/three-pl/import` - Import (three-pl.import)

### 5. Seeders
- `database/seeders/ThreePlPermissionSeeder.php` - Permissions seeder
- `database/seeders/GrantThreePlPermissionsSeeder.php` - Grant permissions to admin role
- `database/seeders/PermissionsSeeder.php` - Updated dengan permissions 3PL (ID 30-35)

### 6. Menu
Menu item sudah ditambahkan di:
- `resources/views/components/layouts/app.blade.php` - Setup > 3PL

## Fitur CRUD

### 1. **List/Index** (three-pl.index)
- Menampilkan daftar 3PL dalam table
- Search berdasarkan nama
- Filter berdasarkan code dan name
- Pagination
- Export ke Excel
- Actions: Edit & Delete

### 2. **Create** (three-pl.create)
Field yang tersedia:
- Kode 3PL (required, unique)
- Nama 3PL (required)
- Active (toggle, default: true)

### 3. **Edit** (three-pl.edit)
Field yang dapat diedit:
- Kode 3PL (required, unique except current record)
- Nama 3PL (required)
- Active (toggle)

### 4. **Import** (three-pl.import)
- Import data dari Excel (.xlsx)
- Template dapat di-download melalui Export dari halaman index

### 5. **Export**
Export semua data 3PL ke file Excel dengan kolom:
- id
- code
- name
- is_active

## Permissions

Permissions yang dibuat (sudah diberikan ke role admin):
- `view three-pl` - Melihat daftar 3PL
- `create three-pl` - Membuat 3PL baru
- `update three-pl` - Mengupdate 3PL
- `delete three-pl` - Menghapus 3PL
- `export three-pl` - Export data 3PL
- `import three-pl` - Import data 3PL

## Testing

Test data sudah dibuat:
```php
Code: 3PL-001
Name: Test 3PL Provider
Status: Active
```

## Cara Menggunakan

1. **Akses Menu**: Login sebagai admin → Setup → 3PL
2. **Create**: Klik tombol "Create" → Isi form → Save
3. **Edit**: Klik icon pensil pada row → Edit data → Save
4. **Delete**: Klik icon trash pada row → Confirm
5. **Export**: Klik tombol "Export" untuk download Excel
6. **Import**: Klik tombol "Import" → Upload file Excel → Import

## Catatan
- Kode 3PL harus unique
- Data yang di-import akan menimpa data existing jika ID sama
- Export dapat digunakan sebagai template untuk import
