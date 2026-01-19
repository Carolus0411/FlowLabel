<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Filterable;
use Illuminate\Database\Query\Expression;

class PurchaseOrder extends Model
{
    use Filterable;

    protected $table = 'purchase_order';
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
        return $this->hasMany(PurchaseOrderDetail::class, 'purchase_order_id', 'id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withDefault();
    }

    public function ppn(): BelongsTo
    {
        return $this->belongsTo(Ppn::class, 'ppn_id', 'id')->withDefault();
    }

    public function pph(): BelongsTo
    {
        return $this->belongsTo(Pph::class, 'pph_id', 'id')->withDefault();
    }

    // Purchase Settlement details connected to this order via purchase_order_code => code
    public function settlementDetails(): HasMany
    {
        return $this->hasMany(PurchaseSettlementDetail::class, 'purchase_order_code', 'code');
    }

    // Purchase Receivals connected to this order
    public function receivals(): HasMany
    {
        return $this->hasMany(PurchaseReceival::class, 'purchase_order_id', 'id');
    }

    // Get receival status based on Purchase Receivals
    public function getReceivalStatusAttribute(): string
    {
        $receivals = $this->receivals()->stored()->get();

        if ($receivals->isEmpty()) {
            return 'pending';
        }

        $hasClosedReceival = $receivals->where('status', 'close')->isNotEmpty();
        $hasOpenReceival = $receivals->where('status', 'open')->isNotEmpty();

        if ($hasClosedReceival && !$hasOpenReceival) {
            return 'received';
        } elseif ($hasOpenReceival) {
            return 'partial';
        }

        return 'pending';
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
            if ($this->settlementDetails()->exists()) {
                $payment_status = 'outstanding';
            } else {
                $payment_status = 'unpaid';
            }
        }

        if ($this->payment_status !== $payment_status) {
            $this->update(['payment_status' => $payment_status]);
        }
    }
}
