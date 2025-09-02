<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramAccount extends Model
{
    protected $fillable = [
        'session_data',
        'json_data',
        'proxy_id',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'json_data' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getSession(): string
    {
        return $this->session_data;
    }

    public function getJsonData(): array
    {
        return $this->json_data;
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
     * Инкремент счетчика использования
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }
}
