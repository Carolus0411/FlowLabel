# MENU CLEANUP - DOKUMENTASI PERUBAHAN

## Tanggal: 15 Januari 2026

## Ringkasan

Telah dilakukan pembersihan menu dan database untuk menyederhanakan sistem LabSysFlow. Hanya menu **Menu Order Label**, **Setup**, dan **Users** yang dipertahankan.

---

## 1. PERUBAHAN MENU (app.blade.php)

### Menu yang DIPERTAHANKAN:

1. **Menu Order Label**

    - Menampilkan dan mengelola order label

2. **Setup** (dengan submenu):

    - Settings
    - Account Mapping
    - Code
    - Draft
    - Send Test Mail
    - Queue Log

3. **Users** (dengan submenu):
    - Users
    - Roles
    - Permissions
    - User Logs

### Menu yang DIHAPUS:

-   Home / Dashboard
-   Request
-   Sales (Sales Order, Delivery Order, Sales Invoice, Sales Invoice Direct, Sales Settlement, AR Outstanding)
-   Purchase (Purchase Order, Purchase Receival, Purchase Invoice, Purchase Invoice Direct, Purchase Settlement, AP Outstanding)
-   Other Payables (Other Payable Invoice, Other Payable Settlement)
-   Cash And Bank (Cash In, Cash Out, Bank In, Bank Out, Intercash, Prepaid Account)
-   Manufacture (Recipe, Bill Of Material, Production)
-   Stock Adjustment (Stock Adjustment In, Stock Adjustment Out)
-   General Ledger (Journal, Opening Balance)
-   Report (Financial, Inventory, Sales, Purchase)
-   Master (Company, Contact, Supplier, PPN, PPH, Chart Of Account, Items Master, Items Group, Item Type, Currency, Uom, Bank, Bank Account, Cash Account)

---

## 2. PERUBAHAN DATABASE

### A. Permissions yang DIPERTAHANKAN (29 permissions):

**Users Permissions:**

-   view users
-   create users
-   update users
-   delete users

**Roles Permissions:**

-   view roles
-   create roles
-   update roles
-   delete roles

**Permissions Permissions:**

-   view permissions
-   create permissions
-   update permissions
-   delete permissions
-   import permissions
-   export permissions

**User Logs:**

-   view user logs

**Order Label Permissions:**

-   view order-label
-   create order-label
-   update order-label
-   delete order-label
-   import order-label
-   export order-label

**Settings Permissions:**

-   view general-setting
-   update general-setting
-   view account-mapping
-   update account-mapping
-   view setting-code
-   update setting-code
-   view draft
-   send test-mail

### B. Tabel yang DIPERTAHANKAN (20 tables):

**Laravel Core Tables:**

-   migrations
-   cache
-   cache_locks
-   sessions
-   failed_jobs
-   jobs
-   job_batches
-   password_reset_tokens

**Authentication & Authorization:**

-   users
-   roles
-   permissions
-   model_has_permissions
-   model_has_roles
-   role_has_permissions
-   user_logs

**Order Label:**

-   order_label
-   order_label_detail

**Settings:**

-   settings
-   companies
-   auto_code

### C. Tabel yang DIHAPUS (67 tables):

-   balance, bank, bank_account, bank_in, bank_in_detail, bank_out, bank_out_detail
-   bom_details, bom_materials, boms
-   cash_account, cash_in, cash_in_detail, cash_out, cash_out_detail
-   coa, contact, currency
-   delivery_order, delivery_order_detail
-   intercash, inventory_ledgers, item_type
-   journal, journal_detail
-   label_orders
-   other_payable_invoice, other_payable_invoice_detail, other_payable_settlement, other_payable_settlement_detail, other_payable_settlement_source
-   pph, ppn, prepaid_account
-   production_details, productions, products
-   purchase_invoice, purchase_invoice_detail, purchase_order, purchase_order_detail, purchase_receival, purchase_receival_detail, purchase_settlement, purchase_settlement_detail, purchase_settlement_source
-   recipe_details, recipes, requests
-   sales_invoice, sales_invoice_detail, sales_invoice_direct, sales_invoice_direct_detail, sales_order, sales_order_detail, sales_settlement, sales_settlement_detail, sales_settlement_prepaid, sales_settlement_source
-   service_charge, service_charge_group
-   stock_adjustment_in, stock_adjustment_in_detail, stock_adjustment_out, stock_adjustment_out_detail
-   supplier, uom

---

## 3. FILE YANG DIMODIFIKASI

1. **resources/views/components/layouts/app.blade.php**

    - Menghapus semua menu kecuali Menu Order Label, Setup, dan Users

2. **database/seeders/PermissionsSeeder.php**

    - Diperbarui untuk hanya menyimpan 29 permissions yang diperlukan

3. **database/seeders/DatabaseSeeder.php**
    - Menghapus seeder yang tidak diperlukan
    - Hanya memanggil: UsersSeeder, RolesSeeder, PermissionsSeeder, ModelHasRolesSeeder, CompaniesSeeder, SettingsSeeder

---

## 4. SCRIPT YANG DIBUAT

File-file script bantuan yang dibuat:

1. `check_permissions.php` - Melihat daftar permissions di database
2. `delete_unnecessary_permissions.php` - Menghapus permissions yang tidak diperlukan
3. `add_required_permissions.php` - Menambahkan permissions yang diperlukan
4. `drop_unnecessary_tables.php` - Script interaktif untuk drop tables
5. `drop_tables_auto.php` - Script otomatis untuk drop tables (sudah dijalankan)

---

## 5. CARA MENGGUNAKAN SETELAH PERUBAHAN

### Reset Database (Opsional - jika ingin fresh install):

```bash
# Truncate permissions dan seed ulang
php artisan db:seed --class=PermissionsSeeder

# Atau seed semua
php artisan db:seed
```

### Verifikasi:

```bash
# Cek permissions
php check_permissions.php

# Cek tables
php artisan tinker --execute="echo json_encode(DB::select('SHOW TABLES'), JSON_PRETTY_PRINT);"
```

---

## 6. STRUKTUR MENU FINAL

```
LabSysFlow
├── Menu Order Label
├── Setup
│   ├── Settings
│   ├── Account Mapping
│   ├── Code
│   ├── Draft
│   ├── Send Test Mail
│   └── Queue Log
└── Users
    ├── Users
    ├── Roles
    ├── Permissions
    └── User Logs
```

---

## 7. CATATAN PENTING

⚠️ **PERINGATAN:**

-   Semua data dari tabel yang dihapus sudah HILANG PERMANEN
-   Backup database telah dibuat sebelum perubahan (jika diperlukan)
-   Routes yang terkait dengan menu yang dihapus masih ada di `routes/web.php` tapi tidak bisa diakses dari menu
-   Models dan Controllers untuk fitur yang dihapus masih ada di direktori `app/`

💡 **REKOMENDASI:**

-   Jika ingin menambahkan kembali fitur yang dihapus, perlu:
    1. Restore tabel dari backup
    2. Tambahkan permissions kembali
    3. Tambahkan menu di app.blade.php
    4. Update DatabaseSeeder.php

---

## 8. STATISTIK PERUBAHAN

-   **Permissions dihapus:** 58 permissions
-   **Permissions dipertahankan:** 29 permissions
-   **Tabel dihapus:** 67 tables
-   **Tabel dipertahankan:** 20 tables
-   **Menu dihapus:** ~20 menu items
-   **Menu dipertahankan:** 3 menu items (dengan submenu)

---

**Perubahan selesai dilakukan pada: 15 Januari 2026**
**Status: ✅ SELESAI**
