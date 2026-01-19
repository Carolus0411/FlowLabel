# Stock Adjustment Multiple Product Architecture

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                   Stock Adjustment In/Out                        │
│  (code, date, status, note, saved, created_by, updated_by)      │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                 1:N HasMany│Relationship
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│              Stock Adjustment Detail (In/Out)                    │
│  (service_charge_id, qty, price, amount, note, timestamps)      │
├─────────────────────────────────────────────────────────────────┤
│  • Multiple records per adjustment                              │
│  • Each represents one product line item                        │
│  • Amount auto-calculated from qty * price                      │
└──────────┬──────────────────────────────────┬────────────────────┘
           │                                  │
    Qty×Price                         FK to service_charge
           │                                  │
           ▼                                  ▼
        Amount                    ┌──────────────────────┐
                                  │  Service Charge      │
                                  │  (Product/Service)   │
                                  │  (with is_stock flag)│
                                  └──────────────────────┘
```

## Component Architecture

```
┌───────────────────────────────────────────┐
│   Livewire: stock-adjustment-in/edit      │
│                                           │
│  • Main form properties:                 │
│    - code, date, note, status            │
│  • Main methods:                         │
│    - save() - validates & saves header   │
│    - close() - approves & dispatches job │
│    - void() - reverts inventory          │
│    - delete() - deletes record           │
└───────────────┬─────────────────────────┘
                │
        Includes (as child component)
                │
                ▼
┌───────────────────────────────────────────┐
│  Livewire: stock-adjustment-in/detail     │
│                                           │
│  • Detail component properties:          │
│    - service_charge_id, qty, price       │
│    - amount, note, mode, drawer          │
│  • Methods:                              │
│    - add() - opens drawer for new item   │
│    - edit() - opens drawer for existing  │
│    - save() - create/update detail       │
│    - delete() - removes detail           │
│  • Features:                             │
│    - Table view with all products        │
│    - Drawer form with validation         │
│    - Edit/Delete buttons per row         │
│    - Only active when adjustment open    │
└───────────────────────────────────────────┘
```

## Approval Workflow

```
┌──────────────────────┐
│  User clicks Approve │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ edit.blade.php: close() method           │
│                                          │
│ • Validates status = 'open'              │
│ • Validates saved = true                 │
│ • Updates status = 'close'               │
│ • Sets approved_by & approved_at         │
│ • Dispatches job                         │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ StockAdjustmentInApprove Job             │
│                                          │
│ • Receives parent adjustment             │
│ • Loops: foreach detail in details       │
│   ├─ Creates InventoryLedger entry      │
│   ├─ service_charge_id from detail      │
│   ├─ qty from detail                    │
│   ├─ price from detail                  │
│   └─ Tracks reference to parent         │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ inventory_ledgers table                  │
│                                          │
│ Entry 1: Product A qty, price            │
│ Entry 2: Product B qty, price            │
│ Entry 3: Product C qty, price            │
│                                          │
│ All linked to parent adjustment          │
│ by reference_id and reference_type       │
└──────────────────────────────────────────┘
```

## Void/Reversal Workflow

```
┌──────────────────────┐
│  User clicks Void    │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ edit.blade.php: void() method            │
│                                          │
│ • Validates status = 'close'             │
│ • Updates status = 'void'                │
│ • Dispatches void job                    │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ StockAdjustmentInVoid Job                │
│                                          │
│ • Deletes ALL InventoryLedger entries   │
│   WHERE:                                 │
│   • reference_type = StockAdjustmentIn  │
│   • reference_id = adjustment.id        │
│                                          │
│ • Works for ANY number of products      │
└──────────┬───────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────┐
│ inventory_ledgers table                  │
│                                          │
│ All entries for this adjustment deleted  │
│ (All 3 products' entries removed)        │
└──────────────────────────────────────────┘
```

## User Interface Layout

```
┌─────────────────────────────────────────────────────┐
│ Stock Adjustment In [Status Badge]                  │
│ [Back] [Void] [Approve] [Save] [Delete]             │
├─────────────────────────────────────────────────────┤
│ General Information                                  │
│ ┌───────────────────┬──────────┬──────────────────┐ │
│ │ Code: SAI-0001    │ Date: __ │ Status: open     │ │
│ └───────────────────┴──────────┴──────────────────┘ │
│                                                      │
│ Note: [___________________________________]         │
│                                                      │
├─────────────────────────────────────────────────────┤
│ Products  [+ Add Product Button]                    │
│ ┌─────────────┬─────────┬─────────┬─────────┬──┐   │
│ │ Product     │ Qty     │ Price   │ Amount  │ #│   │
│ ├─────────────┼─────────┼─────────┼─────────┼──┤   │
│ │ Product A   │ 100.00  │ 50.00   │ 5000.00 │✏ │   │
│ │ Product B   │  50.00  │ 75.00   │ 3750.00 │✏ │   │
│ │ Product C   │  25.00  │ 100.00  │ 2500.00 │✏ │   │
│ ├─────────────┼─────────┼─────────┼─────────┼──┤   │
│ │ Total       │ 175.00  │         │11250.00 │  │   │
│ └─────────────┴─────────┴─────────┴─────────┴──┘   │
│                                                      │
│ [Add Product Drawer - when clicked]                │
│ ┌──────────────────────────────────────────────┐   │
│ │ Product: [Dropdown search _______________]   │   │
│ │ Qty: [_______________]                      │   │
│ │ Price: [_______________]                    │   │
│ │ Note: [_____________________]                │   │
│ │ [Cancel] [Save]                             │   │
│ └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

## Key Design Patterns

### 1. Detail Component Pattern

-   Main form simplified to header-only
-   Detail component injected as Livewire child
-   Detail component manages full CRUD
-   Follows Mary UI conventions

### 2. Transaction Tracking

-   Each detail creates separate InventoryLedger
-   All entries linked via reference_id
-   Enables easy reversal and audit trail

### 3. Validation Strategy

-   Main form validates: code, date required
-   Main form validates: at least 1 detail exists
-   Detail form validates: product, qty, price required
-   Cast::number ensures precision

### 4. State Management

-   Drawer manages add/edit modes
-   Selected property tracks editing item
-   Status controls read-only vs editable

## Database Relations Summary

```
stock_adjustment_in
    ├─ many stock_adjustment_in_detail
    │   ├─ many service_charges (product info)
    │   └─ creates inventory_ledgers (on approval)
    ├─ belongs to users (created_by, updated_by, approved_by)
    └─ has many inventory_ledgers (by reference)

stock_adjustment_out
    ├─ many stock_adjustment_out_detail
    │   ├─ many service_charges (product info)
    │   └─ creates inventory_ledgers (on approval)
    ├─ belongs to users (created_by, updated_by, approved_by)
    └─ has many inventory_ledgers (by reference)
```

## Inventory Ledger Structure (Per Detail)

```
InventoryLedger Entry:
├─ date: 2025-12-11
├─ service_charge_id: 1 (from detail)
├─ qty: 100.00 (from detail)
├─ price: 50.00 (from detail)
├─ type: 'in' or 'out'
├─ transaction_source: 'Stock Adjustment In'
├─ reference_number: 'SAI-0001' (from parent)
├─ reference_type: 'App\Models\StockAdjustmentIn' (class name)
└─ reference_id: 1 (parent adjustment ID)

Enables:
✓ Audit trail - all adjustments trackable
✓ Easy reversal - delete by reference
✓ Inventory tracking - accurate stock movements
✓ Reporting - can group by transaction source
```
