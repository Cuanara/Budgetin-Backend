<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="01a1f7a2-cf56-71b0-acd3-c747f1420c7d"),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", example="income"),
 *     @OA\Property(property="amount", type="number", format="float", example=1000000),
 *     @OA\Property(property="description", type="string", example="Pendapatan bulan ini"),
 *     @OA\Property(property="transaction_date", type="string", format="date-time", example="2026-06-06T00:00:00Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2026-11-20T06:26:44Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2026-11-20T06:26:44Z")
 * )
 */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
