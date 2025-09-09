<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewTask extends Model
{
    protected $fillable = [
        'telegram_post_url',
        'views_count',
        'user_id',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Получить пользователя, создавшего задачу
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
