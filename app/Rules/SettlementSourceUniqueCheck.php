<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Schema;

class SettlementSourceUniqueCheck implements ValidationRule
{
    protected string $settleableType;
    protected string $sourceTable;
    protected string $settlementForeignKey;
    protected ?string $currentSettlementCode;

    /**
     * @param string $settleableType The model class (e.g., CashIn, BankIn, CashOut, BankOut)
     * @param string $sourceTable The settlement source table (e.g., sales_settlement_source, purchase_settlement_source)
     * @param string $settlementForeignKey The foreign key column in source table (e.g., sales_settlement_code, purchase_settlement_code)
     * @param string|null $currentSettlementCode Current settlement code to exclude from check (for edit mode)
     */
    public function __construct(
        string $settleableType,
        string $sourceTable,
        string $settlementForeignKey,
        ?string $currentSettlementCode = null
    ) {
        $this->settleableType = $settleableType;
        $this->sourceTable = $sourceTable;
        $this->settlementForeignKey = $settlementForeignKey;
        $this->currentSettlementCode = $currentSettlementCode;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Check if this source code is already used in another saved settlement
        $exists = \DB::table($this->sourceTable)
            ->where('settleable_type', $this->settleableType)
            ->where('settleable_id', $value)
            ->when($this->currentSettlementCode, function ($query) {
                // Exclude current settlement when editing
                $query->where($this->settlementForeignKey, '!=', $this->currentSettlementCode);
            })
            ->whereExists(function ($query) {
                // Only check against saved and non-void settlements
                $settlementTable = str_contains($this->sourceTable, 'sales') ? 'sales_settlement' : 'purchase_settlement';
                $query->select(\DB::raw(1))
                    ->from($settlementTable)
                    ->whereColumn($settlementTable . '.code', $this->sourceTable . '.' . $this->settlementForeignKey)
                    ->where($settlementTable . '.saved', 1)
                    ->where($settlementTable . '.status', '!=', 'void');
            })
            ->exists();

        if ($exists) {
            $fail('This payment code has already been used in another settlement.');
        }
    }
}
