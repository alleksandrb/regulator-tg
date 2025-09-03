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

    public function createAccount(string $session, string $jsonData): TelegramAccount
    {
        $proxy = $this->proxyRepository->getProxyWithMinUsage();

        return TelegramAccount::query()->create([
            'session_data' => $session,
            'json_data' => $jsonData,
            'proxy_id' => $proxy->id,
        ]);
    }
}