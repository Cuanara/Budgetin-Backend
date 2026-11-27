<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use HasUuids, SoftDeletes;

    public const NOTIFICATION_CHANNELS = ['wishlist_delay_ended'];

    /**
     * Nilai default bila channel belum pernah diatur.
     */
    public const DEFAULT_NOTIFICATIONS = [
        'wishlist_delay_ended' => true,
    ];

    protected $fillable = [
        'user_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Pengaturan notifikasi yang sudah digabung dengan nilai default.
     */
    public function notificationSettings(): array
    {
        return array_merge(
            self::DEFAULT_NOTIFICATIONS,
            $this->settings['notifications'] ?? []
        );
    }
}
