<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Filterable;

class PrepaidAccount extends Model
{
    use Filterable;

    protected $table = 'prepaid_account';
    protected $guarded = [];

    // Prepaid account COA codes
    public const COA_CUSTOMER_DOWN_PAYMENT = '204-001';
    public const COA_REFUNDABLE_CUSTOMER_DEPOSIT = '204-002';

    public static function getPrepaidCoaCodes(): array
    {
        return [
            self::COA_CUSTOMER_DOWN_PAYMENT,
            self::COA_REFUNDABLE_CUSTOMER_DEPOSIT,
        ];
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class, 'coa_code', 'code')->withDefault();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id')->withDefault();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id')->withDefault();
    }

    /**
     * Get the party name (contact or supplier)
     */
    public function getPartyNameAttribute(): string
    {
        if ($this->contact_id) {
            return $this->contact->name ?? '';
        }
        if ($this->supplier_id) {
            return $this->supplier->name ?? '';
        }
        return '';
    }

    #[Scope]
    protected function forContact(Builder $query, int $contactId): void
    {
        $query->where('contact_id', $contactId);
    }

    #[Scope]
    protected function forSupplier(Builder $query, int $supplierId): void
    {
        $query->where('supplier_id', $supplierId);
    }
}
