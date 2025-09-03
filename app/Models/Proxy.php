<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proxy extends Model
{
    protected $fillable = [
        'name',
        'ip',
        'port',
        'protocol',
        'login',
        'password',
        'refresh_link',
        'usage_count',
        'last_used_at',
        'is_active',
        'max_accounts',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'port' => 'integer',
        'usage_count' => 'integer',
        'max_accounts' => 'integer',
    ];

    /**
     * Получить аккаунты, использующие этот прокси
     */
    public function telegramAccounts(): HasMany
    {
        return $this->hasMany(TelegramAccount::class);
    }

    /**
     * Скоуп для получения активных прокси
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Проверить, не превышен ли лимит аккаунтов
     */
    public function hasReachedAccountLimit(): bool
    {
        return $this->telegramAccounts()->count() >= $this->max_accounts;
    }
}
