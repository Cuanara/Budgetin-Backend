<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'transaction_date',
        'notes',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->wallet_id) && ! empty($transaction->user_id)) {
                $wallet = Wallet::where('user_id', $transaction->user_id)->first();
                if ($wallet) {
                    $transaction->wallet_id = $wallet->id;
                }
            }
        });
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['notes'] ?? null;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['notes'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
