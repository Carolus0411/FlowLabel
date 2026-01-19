# Stock Adjustment Module Refactoring - Multiple Product Support

## Overview

Refactored Stock Adjustment In/Out modules to support multiple products per adjustment using a detail pattern, matching the existing Cash-Out module structure.

## Changes Made

### 1. Database Migrations (Already Created)

-   `database/migrations/2025_12_11_043221_create_stock_adjustment_in_detail_table.php`
-   `database/migrations/2025_12_11_043226_create_stock_adjustment_out_detail_table.php`

Both create tables with structure:

-   `id` (PK)
-   `stock_adjustment_in_id` / `stock_adjustment_out_id` (FK)
-   `service_charge_id` (FK to service_charges)
-   `qty` (12,4) - quantity with 4 decimal places
-   `price` (12,2) - unit price
-   `amount` (12,2) - calculated qty \* price
-   `note` (text)
-   `timestamps`

### 2. Models Created

-   `app/Models/StockAdjustmentInDetail.php` - Detail model with relationships
-   `app/Models/StockAdjustmentOutDetail.php` - Detail model with relationships

**Relationships:**

-   `BelongsTo` StockAdjustmentIn/Out (parent)
-   `BelongsTo` ServiceCharge (product)

### 3. Model Updates

-   `app/Models/StockAdjustmentIn.php` - Added `details()` HasMany relationship
-   `app/Models/StockAdjustmentOut.php` - Added `details()` HasMany relationship

### 4. Main Edit Components Refactored

-   `resources/views/livewire/stock-adjustment-in/edit.blade.php`
-   `resources/views/livewire/stock-adjustment-out/edit.blade.php`

**Changes:**

-   Removed individual product selection from main form
-   Removed single service_charge_id, qty, price properties from component
-   Added validation to ensure at least one detail exists before save
-   Simplified form to only show: code, date, note, status, approval info
-   Added `<livewire:stock-adjustment-in.detail :id="..." />` to include detail component
-   Added `<livewire:stock-adjustment-out.detail :id="..." />` to include detail component

### 5. Detail Livewire Components Created

-   `resources/views/livewire/stock-adjustment-in/detail.blade.php`
-   `resources/views/livewire/stock-adjustment-out/detail.blade.php`

**Features:**

-   Full CRUD operations (Add, Edit, Delete products)
-   Product selection via x-choices-offline dropdown
-   Qty and Price inputs with proper validation
-   Automatic amount calculation (qty \* price)
-   Note field for additional details
-   Table view showing all products with formatting
-   Drawer form for add/edit operations
-   Edit/Delete action buttons per product
-   Respects adjustment status (read-only when closed/voided)

**Form Validation:**

-   service_charge_id: required
-   qty: required, numeric
-   price: required, numeric
-   note: required

### 6. Approval Jobs Refactored

-   `app/Jobs/StockAdjustmentInApprove.php`
-   `app/Jobs/StockAdjustmentOutApprove.php`

**Changes:**

-   Changed from single InventoryLedger creation to loop through all details
-   Creates separate InventoryLedger entry for each product detail
-   Maintains correct transaction tracking with reference_id and reference_number

### 7. Void Jobs (No Changes Needed)

-   `app/Jobs/StockAdjustmentInVoid.php`
-   `app/Jobs/StockAdjustmentOutVoid.php`

Already delete all InventoryLedger entries by reference_type and reference_id, so they handle multiple details correctly.

## Workflow

### Create Stock Adjustment In:

1. Create new record (auto-generates code)
2. Set date and general note
3. Save (sets saved = 1)
4. Use detail component to add products:
    - Click "Add Product" button
    - Select product from dropdown
    - Enter quantity and price
    - Add optional note
    - Save (calculates amount = qty \* price)
    - Repeat for multiple products
5. Approve (creates inventory ledger entry for each product)

### Approve Process:

1. Validates at least one product exists
2. Creates StockAdjustmentInApprove/Out job
3. Job iterates through all details
4. Creates InventoryLedger entry per detail with:
    - service_charge_id from detail
    - qty and price from detail
    - transaction_source: "Stock Adjustment In/Out"
    - reference_number: adjustment code
    - reference_type: StockAdjustmentIn/Out class
    - reference_id: adjustment id

### Void Process:

1. Deletes all InventoryLedger entries where:
    - reference_type = StockAdjustmentIn/Out class
    - reference_id = adjustment id
2. Works correctly for multiple products

## Benefits

1. **Multiple Products Support**: Each adjustment can now contain multiple products
2. **UI Consistency**: Matches Cash-Out module detail pattern
3. **Better Data Structure**: Proper relational design with detail tables
4. **Correct Inventory Tracking**: Each product creates separate inventory ledger entry
5. **Flexible Workflow**: Add/edit/delete products before approval
6. **Scalability**: Same pattern can be reused for other modules

## Testing Checklist

-   [ ] Create stock adjustment with multiple products
-   [ ] Edit products (change qty, price, note)
-   [ ] Delete products from adjustment
-   [ ] Approve adjustment (verify inventory ledger entries created for each product)
-   [ ] View inventory ledger and confirm entries
-   [ ] Void adjustment (verify all inventory ledger entries deleted)
-   [ ] Test with different product types
-   [ ] Verify proper currency formatting in tables
-   [ ] Test on different screen sizes (responsive UI)
