# Modul Intercash

## Deskripsi

Modul Intercash adalah fitur untuk memindahkan dana antar akun Bank dan Cash dengan approval workflow dan automatic journal creation. Modul ini mendukung transfer:

-   Bank ke Cash
-   Cash ke Bank
-   Bank ke Bank
-   Cash ke Cash

## Fitur Utama

### 1. Transaksi Intercash

-   Membuat transaksi transfer dana antar akun
-   Support multi-currency dengan kurs otomatis
-   Automatic code generation untuk setiap transaksi
-   Status tracking (Open, Approve, Void)

### 2. Approval Workflow

Ketika transaksi Intercash di-approve, sistem akan secara otomatis:

1. **Membuat Transaksi OUT** (dari akun sumber)

    - Jika dari Cash Account → membuat Cash Out
    - Jika dari Bank Account → membuat Bank Out
    - Detail: Debet ke akun 101-109 (Inter Cash)

2. **Membuat Transaksi IN** (ke akun tujuan)

    - Jika ke Cash Account → membuat Cash In
    - Jika ke Bank Account → membuat Bank In
    - Detail: Credit dari akun 101-109 (Inter Cash)

3. **Generate Journal Entries**

    - Journal untuk Bank/Cash Out dengan entry:

        - Debet: 101-109 (Inter Cash)
        - Credit: Bank/Cash Account sumber

    - Journal untuk Bank/Cash In dengan entry:
        - Debet: Bank/Cash Account tujuan
        - Credit: 101-109 (Inter Cash)

### 3. Status Tracking

-   **Open**: Transaksi baru dibuat, masih bisa diedit
-   **Approve**: Sudah di-approve, transaksi OUT/IN dan journal sudah dibuat
-   **Void**: Transaksi dibatalkan, semua transaksi terkait juga di-void

## Cara Penggunaan

### Membuat Transaksi Intercash Baru

1. **Akses Menu**

    - Navigasi ke menu: **Cash And Bank > Intercash**
    - Klik tombol **Create**

2. **Isi Form Transaksi**
    - **Date**: Tanggal transaksi
    - **Description**: Keterangan transaksi
3. **Pilih From Account (Sumber Dana)**
    - **From Account Type**: Pilih Cash atau Bank
    - **From Account**: Pilih akun spesifik
4. **Pilih To Account (Tujuan Dana)**
    - **To Account Type**: Pilih Cash atau Bank
    - **To Account**: Pilih akun tujuan
5. **Isi Amount**
    - **Currency**: Pilih mata uang
    - **Kurs**: Rate konversi (default 1 untuk IDR)
    - **Foreign Amount**: Jumlah dalam mata uang asing
    - **Amount (IDR)**: Otomatis terhitung
6. **Save** transaksi

### Approve Transaksi

1. **Buka transaksi** yang sudah di-save (status Open)
2. Klik tombol **Approve**
3. Sistem akan otomatis:
    - Membuat transaksi Cash/Bank Out
    - Membuat transaksi Cash/Bank In
    - Generate journal entries untuk kedua transaksi
    - Update status menjadi "Approve"
    - Menyimpan reference code transaksi OUT dan IN

### View Generated Transactions

Setelah di-approve, pada form detail akan muncul section **Generated Transactions** dengan:

-   **Transaction OUT**: Link ke Cash Out atau Bank Out yang dibuat
-   **Transaction IN**: Link ke Cash In atau Bank In yang dibuat
-   Icon mata (👁️) untuk melihat detail transaksi

### Void Transaksi

1. **Buka transaksi** yang sudah di-approve
2. Klik tombol **Void**
3. Confirm untuk membatalkan
4. Sistem akan otomatis:
    - Void transaksi Cash/Bank Out terkait
    - Void transaksi Cash/Bank In terkait
    - Void journal entries terkait
    - Update status menjadi "Void"

## Contoh Journal Entries

### Contoh 1: Bank to Cash Transfer

**Transaksi**: Transfer Rp 10.000.000 dari BCA ke Cash On Hand

**Bank Out Journal:**

```
Debet: 101-109 Inter Cash         Rp 10.000.000
Credit: 102-002 BCA 069-3151888   Rp 10.000.000
```

**Cash In Journal:**

```
Debet: 101-001 Cash On Hand IDR   Rp 10.000.000
Credit: 101-109 Inter Cash        Rp 10.000.000
```

### Contoh 2: Bank to Bank Transfer

**Transaksi**: Transfer Rp 5.000.000 dari BCA ke Mandiri

**Bank Out Journal (BCA):**

```
Debet: 101-109 Inter Cash         Rp 5.000.000
Credit: 102-002 BCA 069-3151888   Rp 5.000.000
```

**Bank In Journal (Mandiri):**

```
Debet: 102-003 Mandiri 123        Rp 5.000.000
Credit: 101-109 Inter Cash        Rp 5.000.000
```

## Permissions

Pastikan user memiliki permission berikut:

-   `view intercash` - Untuk melihat list intercash
-   `create intercash` - Untuk membuat transaksi baru
-   `update intercash` - Untuk edit transaksi (status open)
-   `approve intercash` - Untuk approve transaksi
-   `void intercash` - Untuk void transaksi
-   `delete intercash` - Untuk delete transaksi (status open)

## Technical Details

### Database Tables

-   **intercash**: Main table untuk transaksi intercash
    -   Kolom penting:
        -   `from_cash_account_id`, `from_bank_account_id`
        -   `to_cash_account_id`, `to_bank_account_id`
        -   `cash_out_id`, `bank_out_id`, `cash_in_id`, `bank_in_id` (reference IDs)
        -   `no_code_from`, `no_code_to` (transaction codes)
        -   `status`, `approved_by`, `approved_at`

### Models & Relations

-   **Intercash Model** memiliki relasi ke:
    -   `fromCashAccount()`, `fromBankAccount()`
    -   `toCashAccount()`, `toBankAccount()`
    -   `cashOut()`, `bankOut()`, `cashIn()`, `bankIn()`
    -   `currency()`, `approvedBy()`

### Jobs

Modul ini menggunakan existing jobs:

-   `BankOutApprove` - Generate journal untuk bank out
-   `BankInApprove` - Generate journal untuk bank in
-   `CashOutApprove` - Generate journal untuk cash out
-   `CashInApprove` - Generate journal untuk cash in
-   `BankOutVoid`, `BankInVoid`, `CashOutVoid`, `CashInVoid` - Void journals

### COA Configuration

Pastikan COA **101-109 (Inter Cash)** sudah dibuat di sistem untuk:

-   Menjadi akun perantara dalam transfer dana
-   Memastikan balance antara OUT dan IN transaction

## Troubleshooting

### Error: "Inter Cash COA not found"

**Solusi**: Buat COA dengan code `101-109` dan nama `Inter Cash`

### Error: "Only open intercash can be approved"

**Solusi**: Pastikan transaksi sudah di-save terlebih dahulu dan statusnya adalah "Open"

### Error: "Only approved intercash can be voided"

**Solusi**: Hanya transaksi yang sudah di-approve yang bisa di-void

### Transaksi tidak bisa diedit setelah approve

**Ini adalah behavior yang benar**. Setelah approve, transaksi sudah membuat journal entries dan tidak bisa diedit lagi. Untuk membatalkan, gunakan fungsi **Void**.

## Best Practices

1. **Selalu verifikasi** amount dan account sebelum approve
2. **Gunakan description** yang jelas untuk memudahkan tracking
3. **Review journal entries** setelah approve untuk memastikan benar
4. **Jangan delete** transaksi yang sudah approve, gunakan Void
5. **Backup data** secara regular karena void tidak bisa di-undo

## Support

Untuk pertanyaan atau issue, hubungi tim development.
