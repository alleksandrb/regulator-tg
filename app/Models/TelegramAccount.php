<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\TelegramFileService;

class TelegramAccount extends Model
{
    protected $fillable = [
        'account_id',
        'filename',
        'proxy_id',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];


    public function getJsonData(): array
    {
        $fileService = app(TelegramFileService::class);
        return $fileService->getJsonData($this->filename);
    }

    /**
     * Получить прокси для этого аккаунта
     */
    public function proxy(): BelongsTo
    {
        return $this->belongsTo(Proxy::class);
    }

    /**
     * Скоуп для получения активных аккаунтов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Скоуп для сортировки по использованию
     */
    public function scopeOrderByUsage($query)
    {
        return $query->orderBy('usage_count', 'asc')
                     ->orderBy('last_used_at', 'asc')
                     ->orderByRaw('last_used_at IS NULL DESC');
    }

    /**
     * Скоуп для поиска по user_id
     */
    public function scopeByUserId($query, $userId)
    {
        return $query->where('account_id', $userId);
    }

    /**
     * Инкремент счетчика использования
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Удалить файлы при удалении модели
     */
    protected static function booted()
    {
        static::deleting(function ($account) {
            if ($account->filename) {
                $fileService = app(TelegramFileService::class);
                $fileService->deleteFiles($account->filename);
            }
        });
    }
}
