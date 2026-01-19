# Transaction Seeder

## Deskripsi

Seeder ini membuat data dummy untuk seluruh transaksi yang ada di sistem finance.

## Data yang Di-generate

### Sales (Penjualan)

-   **Sales Orders**: 20 transaksi order penjualan dengan status random (open/close)
-   **Sales Invoices**: 30 invoice penjualan dengan status pembayaran bervariasi (paid/unpaid/outstanding)

### Purchase (Pembelian)

-   **Purchase Orders**: 20 transaksi order pembelian dengan status random (open/close)
-   **Purchase Invoices**: 30 invoice pembelian dengan status pembayaran bervariasi (paid/unpaid/outstanding)

### Cash Transactions (Transaksi Kas)

-   **Cash In**: 25 transaksi kas masuk dengan nominal Rp 500,000 - Rp 10,000,000
-   **Cash Out**: 25 transaksi kas keluar dengan nominal Rp 500,000 - Rp 8,000,000

### Bank Transactions (Transaksi Bank)

-   **Bank In**: 25 transaksi bank masuk dengan nominal Rp 1,000,000 - Rp 20,000,000
-   **Bank Out**: 25 transaksi bank keluar dengan nominal Rp 1,000,000 - Rp 15,000,000

## Cara Menggunakan

### Menjalankan Seeder

```bash
php artisan db:seed --class=TransactionSeeder
```

### Menjalankan Semua Seeder (termasuk TransactionSeeder)

```bash
php artisan db:seed
```

## Persyaratan

Sebelum menjalankan seeder ini, pastikan seeder berikut sudah dijalankan:

-   UsersSeeder (untuk autentikasi)
-   ContactSeeder (untuk customer/client)
-   SupplierSeeder (untuk supplier)
-   CashAccountSeeder (untuk akun kas)
-   BankAccountSeeder (untuk akun bank)
-   CurrencySeeder (untuk mata uang)
-   CoaSeeder (untuk Chart of Accounts)

## Fitur

-   Menggunakan tanggal random dalam 1 tahun terakhir
-   Nominal transaksi random sesuai range yang realistis
-   Status pembayaran yang bervariasi (paid, unpaid, outstanding)
-   Kode transaksi unik dengan format: {TYPE}-2025-{NUMBER}
-   Semua transaksi sudah dalam status 'close' dan 'saved'
-   Menghindari duplikasi dengan mengecek ID terakhir

## Catatan

-   Semua transaksi dibuat dengan user ID pertama sebagai creator/updater
-   Data detail transaksi (item per baris) tidak di-generate untuk menjaga kesederhanaan
-   Transaksi cash/bank memiliki 1 detail entry ke COA (Chart of Accounts)
-   Semua invoice memiliki balance_amount yang bervariasi untuk simulasi piutang/hutang

## Total Data yang Dihasilkan

**Total sekitar 170 transaksi utama** yang tersebar di berbagai modul transaksi keuangan.
