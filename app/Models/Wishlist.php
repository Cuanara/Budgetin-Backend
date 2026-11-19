<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wishlist extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'savings_goal_id',
        'item_name',
        'purchase_url',
        'price',
        'saved_amount',
        'delay_days',
        'delay_until',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'saved_amount' => 'decimal:2',
            'delay_days' => 'integer',
            'delay_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function savingsGoal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class);
    }
}

