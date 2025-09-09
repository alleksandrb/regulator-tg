<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TelegramAccount;
use Illuminate\Support\Facades\DB;

class TelegramAccountRepository
{
    public function __construct(
        private ProxyRepository $proxyRepository
    ) {}

    /**
     * Выбрать доступный аккаунт с минимальным использованием
     * Использует SELECT ... FOR UPDATE SKIP LOCKED для избежания race conditions
     */
    public function selectAvailableAccount(): ?TelegramAccount
    {
        return DB::transaction(function () {
            $account = TelegramAccount::query()->active()
                ->orderByUsage()
                ->lockForUpdate()
                ->skip(0)
                ->take(1)
                ->first();

            if ($account) {
                // Сразу инкрементируем счетчик, чтобы другие запросы не выбрали тот же аккаунт
                $account->increment('usage_count');
                $account->update(['last_used_at' => now()]);
            }

            return $account;
        });
    }

    public function createAccount(string $session, string $jsonData, string|int|null $userId = null): TelegramAccount
    {
        $proxy = $this->proxyRepository->getProxyWithMinUsage();

        $account = TelegramAccount::query()->create([
            'user_id' => $userId ? (string)$userId : null,
            'session_data' => $session,
            'json_data' => $jsonData,
            'proxy_id' => $proxy->id,
        ]);

        // Обновляем статистику прокси
        $proxy->incrementUsage();

        return $account;
    }

    /**
     * Проверить, существует ли аккаунт с таким user_id
     */
    public function existsByUserId(string|int $userId): bool
    {
        return TelegramAccount::query()->byUserId((string)$userId)->exists();
    }

    /**
     * Получить аккаунт по user_id
     */
    public function getByUserId(string|int $userId): ?TelegramAccount
    {
        return TelegramAccount::query()->byUserId((string)$userId)->first();
    }
}