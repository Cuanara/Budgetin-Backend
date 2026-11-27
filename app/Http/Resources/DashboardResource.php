<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_balance' => $this->resource['current_balance'] ?? [
                'amount' => 0.0,
            ],
            'current_savings' => $this->resource['current_savings'] ?? [
                'amount' => 0.0,
                'total_wishlist_price' => 0.0,
                'remaining_for_wishlist' => 0.0,
            ],
            'monthly_expense' => $this->resource['monthly_expense'] ?? [
                'total' => 0.0,
                'percentage_of_budget' => 0.0,
                'by_category' => [],
            ],
            'expense_summary' => $this->resource['expense_summary'] ?? [
                'percentage_used' => 0.0,
                'spent_amount' => 0.0,
                'budget_amount' => 0.0,
            ],
            'recent_transactions' => $this->resource['recent_transactions'] ?? [],
        ];
    }
}