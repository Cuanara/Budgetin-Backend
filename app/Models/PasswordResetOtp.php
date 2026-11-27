<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PasswordResetOtp extends Model
{
    use HasUuids, Prunable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'code',
        'attemps',
        'expires_at',
    ];

    protected $hidden = ['code'];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts' => 'integer',
];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    // menghapus OTP kadaluarsa lebih dari satu hari.
    public function prunable()
    {
        return static::where('expires_at', '<', now()->subDay());
    }
}
