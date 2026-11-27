<?php

namespace App\Http\Resources;

use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Wishlist
 */
class WishlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = (float) $this->price;
        $savedAmount = (float) $this->saved_amount;
        $progressPercentage = $price > 0 ? min(100, round(($savedAmount / $price) * 100, 1)) : 0.0;

        if ($this->status === 'archived') {
            $remainingDays = (int) $this->delay_days;
        } elseif ($this->delay_until !== null) {
            $remainingDays = max(0, (int) Carbon::today()->diffInDays($this->delay_until->startOfDay(), false));
        } else {
            $remainingDays = 0;
        }

        return [
            'id' => $this->id,
            'savings_goal_id' => $this->savings_goal_id,
            'item_name' => $this->item_name,
            'purchase_url' => $this->purchase_url,
            'price' => $price,
            'saved_amount' => $savedAmount,
            'remaining_amount' => max(0.0, $price - $savedAmount),
            'progress_percentage' => $progressPercentage,
            'delay_days' => (int) $this->delay_days,
            'delay_until' => $this->delay_until?->format('Y-m-d'),
            'remaining_days' => $remainingDays,
            'is_countdown_paused' => $this->status === 'archived',
            'is_ready_to_decide' => $this->status === 'pending_decision',
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

