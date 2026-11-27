<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    public const PLATFORM_WEB = 'web';

    public const PLATFORM_ANDROID = 'android';

    public const PLATFORM_IOS = 'ios';

    protected $fillable = [
        'user_id',
        'token',
        'platform',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
