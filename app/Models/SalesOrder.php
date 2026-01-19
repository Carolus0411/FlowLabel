<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Filterable;
use Illuminate\Database\Query\Expression;

class SalesOrder extends Model
{
    use Filterable;

    protected $table = 'sales_order';
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
        return $this->hasMany(SalesOrderDetail::class, 'sales_order_id', 'id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id')->withDefault();
    }

    public function ppn(): BelongsTo
    {
        return $this->belongsTo(Ppn::class, 'ppn_id', 'id')->withDefault();
    }

    public function pph(): BelongsTo
    {
        return $this->belongsTo(Pph::class, 'pph_id', 'id')->withDefault();
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'sales_order_id', 'id');
    }

    public function getDoStatusAttribute(): string
    {
        $deliveryOrders = $this->deliveryOrders;

        if ($deliveryOrders->isEmpty()) {
            return 'No DO';
        }

        $statuses = $deliveryOrders->pluck('status')->unique();

        if ($statuses->count() == 1) {
            return ucfirst($statuses->first());
        }

        return 'Mixed';
    }

    public function recalcPaymentStatus(): void
    {
        if ($this->balance_amount instanceof Expression) {
            $this->refresh();
        }
        $payment_status = 'unpaid';

        if ($this->balance_amount == 0) {
            $payment_status = 'paid';
        } else {
             $payment_status = 'unpaid';
        }

        $this->payment_status = $payment_status;
        $this->saveQuietly();
    }
}
