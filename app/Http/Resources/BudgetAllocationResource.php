<?php

namespace App\Http\Resources;

use App\Models\BudgetAllocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BudgetAllocation
 */
class BudgetAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalIncome = (float) $this->budget?->total_income;
        $allocated  = (float) $this->allocated_amount;
        $spent      = (float) ($this->spent_amount ?? 0);
        $remaining  = $allocated - $spent;
        $pctUsed    = $allocated > 0 ? round(($spent / $allocated) * 100, 2) : 0;

        return [
            'id'               => $this->id,
            'category_id'      => $this->category_id,
            'name'             => $this->category?->name ?? $this->custom_name,
            'percentage'       => (float) $this->percentage,
            'allocated_amount' => $allocated,
            'spent_amount'     => $spent,
            'remaining_amount' => $remaining,
            'percentage_used'  => $pctUsed,
        ];
    }
}

