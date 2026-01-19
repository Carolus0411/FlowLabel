# Order Label Dashboard - Platform Breakdown Analysis

## Overview
Dashboard telah diubah dari menampilkan "Status Breakdown" menjadi "Platform Breakdown" yang menampilkan distribusi order berdasarkan platform e-commerce.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ORDER LABEL DASHBOARD                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    STATISTICS CARDS                          │  │
│  ├────────────┬────────────┬────────────┬────────────────────┐  │  │
│  │ Total      │ Printed    │ Pending    │ Total              │  │  │
│  │ Orders     │ Orders     │ Print      │ Batches            │  │  │
│  │            │            │            │                    │  │  │
│  │ [NUMBER]   │ [NUMBER]   │ [NUMBER]   │ [NUMBER]           │  │  │
│  └────────────┴────────────┴────────────┴────────────────────┘  │  │
│                                                                       │
│  ┌──────────────────────────────────┬─────────────────────────────┐│
│  │  MAIN CONTENT (2 columns)        │ SIDEBAR (1 column)          ││
│  ├──────────────────────────────────┼─────────────────────────────┤│
│  │                                  │ ┌─────────────────────────┐ ││
│  │  ┌──────────────────────────┐   │ │  PLATFORM BREAKDOWN     │ ││
│  │  │   DAILY STATISTICS       │   │ │  (Doughnut Chart)       │ ││
│  │  │   (Line Chart)           │   │ │                         │ ││
│  │  │                          │   │ │      [CHART]            │ ││
│  │  │   • Printed (Green)      │   │ │                         │ ││
│  │  │   • Not Printed (Orange) │   │ │  Platform List:         │ ││
│  │  │                          │   │ │  ┌─────────────────┐    │ ││
│  │  └──────────────────────────┘   │ │  │ □ Lazada  1,234 │    │ ││
│  │                                  │ │  │ □ Shopee    856 │    │ ││
│  │  ┌──────────────────────────┐   │ │  │ □ TikTok    523 │    │ ││
│  │  │  RECENT PRINTED ORDERS   │   │ │  │ □ Tokopedia 412 │    │ ││
│  │  │  (Table)                 │   │ │  └─────────────────┘    │ ││
│  │  │                          │   │ └─────────────────────────┘ ││
│  │  │  ID | Platform | Date    │   │                             ││
│  │  │  ───────────────────────  │   │ ┌─────────────────────────┐ ││
│  │  │  123 | Lazada   | Today  │   │ │  PRINT EFFICIENCY       │ ││
│  │  │  124 | Shopee   | Today  │   │ │  (Radial Progress)      │ ││
│  │  │  ...                     │   │ │                         │ ││
│  │  └──────────────────────────┘   │ │     ( 85% )             │ ││
│  │                                  │ │                         │ ││
│  └──────────────────────────────────┴─────────────────────────────┘│
│                                                                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │              TOP BATCHES BY PAGES                            │  │
│  │  (5 Horizontal Cards)                                        │  │
│  │                                                              │  │
│  │  [Batch 1] [Batch 2] [Batch 3] [Batch 4] [Batch 5]         │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌─────────────────┐
│   User Action   │
│  (Select Period)│
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────┐
│     Livewire Component              │
│   (dashboard.blade.php)             │
├─────────────────────────────────────┤
│  • Set $period variable             │
│  • Calculate date range             │
│  • Build query with filters         │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│      Database Queries               │
├─────────────────────────────────────┤
│  1. Total Orders Count              │
│  2. Printed Orders Count            │
│  3. Platform Breakdown              │
│     SELECT platform,                │
│            COUNT(*) as count        │
│     FROM order_labels               │
│     WHERE saved = 1                 │
│     GROUP BY platform               │
│     ORDER BY count DESC             │
│                                     │
│  4. Recent Printed Orders           │
│  5. Top Batches                     │
│  6. Daily Statistics                │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│    Data Processing                  │
├─────────────────────────────────────┤
│  • Calculate percentages            │
│  • Format numbers                   │
│  • Group by platform                │
│  • Map platform colors              │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│      View Rendering                 │
├─────────────────────────────────────┤
│  • Stats Cards (DaisyUI)            │
│  • Platform Chart (Chart.js)        │
│  • Platform List with badges        │
│  • Responsive grid layout           │
└────────┬────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────┐
│    Chart.js Rendering               │
├─────────────────────────────────────┤
│  Doughnut Chart:                    │
│  • Labels: Platform names           │
│  • Data: Order counts               │
│  • Colors: Platform-specific        │
│  • Tooltip: Count + Percentage      │
└─────────────────────────────────────┘
```

## Platform Breakdown Implementation

### 1. Database Query
```php
$platformBreakdown = (clone $query)
    ->select('platform', DB::raw('count(*) as count'))
    ->groupBy('platform')
    ->orderBy('count', 'desc')
    ->get()
    ->mapWithKeys(fn($item) => [$item->platform => $item->count]);
```

**Output Example:**
```php
[
    'Lazada' => 1234,
    'Shopee' => 856,
    'TikTok' => 523,
    'Tokopedia' => 412,
]
```

### 2. Platform Color Mapping
```javascript
const platformColors = {
    'Lazada':     'hsl(var(--p))',   // Primary (Blue)
    'Shopee':     'hsl(var(--s))',   // Secondary (Purple)
    'TikTok':     'hsl(var(--a))',   // Accent (Pink)
    'Tokopedia':  'hsl(var(--su))',  // Success (Green)
    'Bukalapak':  'hsl(var(--er))',  // Error (Red)
    'Blibli':     'hsl(var(--wa))',  // Warning (Orange)
};
```

### 3. UI Components

#### A. Platform List with Badges
```blade
@foreach($platformBreakdown as $platform => $count)
    <div class="flex items-center justify-between">
        <div class="badge badge-primary">
            [Icon] {{ $platform }}
        </div>
        <span class="text-2xl font-bold">{{ number_format($count) }}</span>
        <span class="text-sm">({{ percentage }}%)</span>
    </div>
@endforeach
```

#### B. Doughnut Chart
- **Type**: Doughnut (donut chart)
- **Size**: Responsive with 60% cutout
- **Features**:
  - Platform-specific colors
  - Hover effect (offset: 10px)
  - Tooltip with count and percentage
  - No legend (shown in list below)

## Platform Badge Icons

| Platform   | Badge Color | Icon               |
|------------|-------------|-------------------|
| Lazada     | Primary     | Shopping Bag      |
| Shopee     | Secondary   | Shopping Cart     |
| TikTok     | Accent      | Video Play        |
| Tokopedia  | Success     | Lightning Bolt    |
| Others     | Neutral     | Book/Generic      |

## Comparison: Before vs After

### Before (Status Breakdown)
```
┌────────────────────────┐
│  Status Breakdown      │
├────────────────────────┤
│ ● Open      150 (60%)  │
│ ● Close      80 (32%)  │
│ ● Cancelled  20 (8%)   │
└────────────────────────┘
```

### After (Platform Breakdown)
```
┌──────────────────────────┐
│  Platform Breakdown      │
│  ┌──────────────────┐   │
│  │   [Doughnut]     │   │
│  │     Chart        │   │
│  └──────────────────┘   │
├──────────────────────────┤
│ ● Lazada    1,234 (48%) │
│ ● Shopee      856 (34%) │
│ ● TikTok      523 (21%) │
│ ● Tokopedia   412 (16%) │
└──────────────────────────┘
```

## Benefits of Platform Breakdown

1. **Business Intelligence**
   - See which platform generates most orders
   - Identify top-performing channels
   - Make data-driven decisions

2. **Resource Allocation**
   - Focus on high-volume platforms
   - Allocate printing resources efficiently
   - Optimize fulfillment process

3. **Trend Analysis**
   - Compare platform performance over time
   - Identify seasonal patterns per platform
   - Track growth/decline by channel

4. **Visual Clarity**
   - Doughnut chart provides instant overview
   - Color-coded platform identification
   - Percentage breakdown at a glance

## Technical Stack

- **Backend**: Laravel Livewire (Volt)
- **Database**: PostgreSQL with aggregation queries
- **Frontend**: DaisyUI (Tailwind CSS)
- **Charts**: Chart.js v4.4.0
- **Icons**: Heroicons (SVG)

## Performance Considerations

1. **Query Optimization**
   - Single query with GROUP BY
   - Indexed platform column
   - Cloned query for different aggregations

2. **Caching Opportunity**
   - Platform breakdown changes infrequently
   - Consider caching for 5-15 minutes
   - Invalidate on new order creation

3. **Chart Performance**
   - Lazy loading Chart.js
   - Doughnut chart is lightweight
   - No real-time updates needed

## Future Enhancements

1. **Platform Filtering**
   - Click platform badge to filter main table
   - Show platform-specific statistics
   - Drill-down analysis

2. **Comparison Mode**
   - Compare periods (this week vs last week)
   - Year-over-year comparison
   - Platform growth trends

3. **Export**
   - Export platform report to PDF/Excel
   - Include chart image
   - Detailed breakdown per platform

4. **Alerts**
   - Alert if platform orders drop significantly
   - Monitor platform API health
   - Notify on unusual patterns

## Related Files

- `resources/views/livewire/order-label/dashboard.blade.php` - Main dashboard
- `app/Models/OrderLabel.php` - Order label model
- `database/migrations/*_create_order_labels_table.php` - Schema

## API Reference

### Livewire Component Properties
- `$period` (string): 'today' | 'week' | 'month' | 'all'

### Computed Properties (with() method)
- `$platformBreakdown` (Collection): Platform => Count mapping
- `$totalOrders` (int): Total order count
- `$totalPrinted` (int): Printed order count
- `$printEfficiency` (float): Percentage of printed orders

### Chart Configuration
- Chart ID: `platformChart`
- Chart Type: `doughnut`
- Cutout: `60%`
- Border Width: `2px`
- Hover Offset: `10px`

---

**Implementation Date**: January 19, 2026
**Status**: ✅ Completed
**Impact**: High - Provides critical business insights
