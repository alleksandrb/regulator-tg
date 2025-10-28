<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'description',
        'is_active',
        'last_used_at',
        'expires_at',
        'allowed_ips',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'allowed_ips' => 'array',
    ];

    /**
     * Генерация нового API токена
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(60));
    }

    /**
     * Создать новый API токен
     */
    public static function createForUser(User $user, string $name, ?string $description = null, ?\DateTime $expiresAt = null, ?array $allowedIps = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => self::generateToken(),
            'description' => $description,
            'expires_at' => $expiresAt,
            'allowed_ips' => $allowedIps,
        ]);
    }

    /**
     * Проверить валидность токена
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Обновить время последнего использования
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Найти токен по значению
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Владелец токена (пользователь)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверить разрешен ли IP-адрес для данного токена
     */
    public function isIpAllowed(string $ip): bool
    {
        // Если allowed_ips не задан (null), разрешаем доступ с любых IP
        if (empty($this->allowed_ips)) {
            return true;
        }

        // Проверяем точное совпадение IP
        if (in_array($ip, $this->allowed_ips, true)) {
            return true;
        }

        // Проверяем подсети (CIDR)
        foreach ($this->allowed_ips as $allowedIp) {
            if ($this->ipInRange($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить находится ли IP в заданном диапазоне (поддержка CIDR)
     */
    private function ipInRange(string $ip, string $range): bool
    {
        // Если это не CIDR нотация, проверяем точное совпадение
        if (!str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $mask] = explode('/', $range);
        
        // Проверяем IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && 
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            
            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int)$mask);
            
            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        // Для IPv6 можно добавить поддержку позже
        return false;
    }

    /**
     * Добавить IP-адрес в список разрешенных
     */
    public function addAllowedIp(string $ip): void
    {
        $allowedIps = $this->allowed_ips ?? [];
        
        if (!in_array($ip, $allowedIps, true)) {
            $allowedIps[] = $ip;
            $this->update(['allowed_ips' => $allowedIps]);
        }
    }

    /**
     * Удалить IP-адрес из списка разрешенных
     */
    public function removeAllowedIp(string $ip): void
    {
        $allowedIps = $this->allowed_ips ?? [];
        
        $allowedIps = array_values(array_filter($allowedIps, fn($allowedIp) => $allowedIp !== $ip));
        
        $this->update(['allowed_ips' => $allowedIps]);
    }
}
