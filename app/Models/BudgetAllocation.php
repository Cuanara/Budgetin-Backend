<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetAllocation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'budget_allocations';

    protected $fillable = [
        'budget_id',
        'category_id',
        'custom_name',
        'percentage',
        'allocated_amount',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

