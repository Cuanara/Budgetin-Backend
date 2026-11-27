<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavingsGoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'target_amount' => $this->target_amount,
            'current_amount' => $this->current_amount,
            'target_date' => $this->target_date,
            'status' => $this->status,
            'wishlists' => WishlistResource::collection($this->whenLoaded('wishlists')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}