<?php

namespace App\Http\Resources;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Budget
 */
class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalIncome = (float) $this->total_income;
        $allocations = $this->allocations ?? collect();

        $totalAllocatedPct    = $allocations->sum(fn ($a) => (float) $a->percentage);
        $availablePct         = max(0, 100 - $totalAllocatedPct);
        $totalAllocatedAmount = $allocations->sum(fn ($a) => (float) $a->allocated_amount);
        $totalSpentAmount     = $allocations->sum(fn ($a) => (float) ($a->spent_amount ?? 0));

        return [
            'id'                        => $this->id,
            'month'                     => $this->month,
            'year'                      => $this->year,
            'total_income'              => $totalIncome,
            'total_allocated_percentage' => round($totalAllocatedPct, 2),
            'available_percentage'      => round($availablePct, 2),
            'total_allocated_amount'    => $totalAllocatedAmount,
            'total_spent_amount'        => $totalSpentAmount,
            'allocations'               => BudgetAllocationResource::collection(
                $allocations->map(function ($allocation) {
                    // inject budget into allocation so the resource can access total_income
                    $allocation->setRelation('budget', $this->resource);
                    return $allocation;
                })
            ),
        ];
    }
}

