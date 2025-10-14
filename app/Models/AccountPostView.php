<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPostView extends Model
{
    protected $fillable = [
        'telegram_account_id',
        'telegram_post_url',
    ];

    /**
     * Связь с аккаунтом
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(TelegramAccount::class, 'telegram_account_id');
    }
}


