<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Filterable;

class DeliveryOrder extends Model
{
    use Filterable;

    protected $table = 'delivery_order';
    protected $guarded = [];

    #[Scope]
    protected function stored(Builder $query): void
    {
        $query->where('saved', 1);
    }

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('status', 'close');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DeliveryOrderDetail::class, 'delivery_order_id', 'id');
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id')->withDefault();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id')->withDefault();
    }

    public function recalcTotals(): void
    {
        $total_qty = $this->details()->sum('qty');
        $total_amount = $this->details()->sum('amount');

        $this->update([
            'total_qty' => $total_qty,
            'total_amount' => $total_amount,
        ]);
    }
}
