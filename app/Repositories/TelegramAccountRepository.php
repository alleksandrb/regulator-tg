<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TelegramAccount;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramFileService;

class TelegramAccountRepository
{
    public function __construct(
        private ProxyRepository $proxyRepository,
        private TelegramFileService $fileService
    ) {}

    /**
     * Выбрать доступный аккаунт с минимальным использованием
     * Использует SELECT ... FOR UPDATE SKIP LOCKED для избежания race conditions
     */
    public function selectAvailableAccount(): ?TelegramAccount
    {
        return DB::transaction(function () {
            // Сначала проверяем, есть ли вообще активные аккаунты
            $activeCount = TelegramAccount::active()->count();
            if ($activeCount === 0) {
                return null;
            }

            $account = TelegramAccount::query()->active()
                ->orderByUsage()
                ->lockForUpdate()
                ->skip(0)
                ->take(1)
                ->first();

            if ($account) {
                // Дополнительная проверка, что аккаунт все еще активен
                if (!$account->is_active) {
                    return null;
                }
                
                // Сразу инкрементируем счетчик, чтобы другие запросы не выбрали тот же аккаунт
                $account->increment('usage_count');
                $account->update(['last_used_at' => now()]);
            }

            return $account;
        });
    }

    public function createAccount(string $session, string $jsonData, string $accountId): TelegramAccount
    {
        $proxy = $this->proxyRepository->getProxyWithMinUsage();

        // Сохраняем файлы на диск и получаем уникальное имя файла
        $filename = $this->fileService->saveFiles($session, $jsonData, $accountId);

        try {
            $account = TelegramAccount::query()->create([
                'account_id' => $accountId,
                'proxy_id' => $proxy->id,
            ]);

            // Обновляем статистику прокси
            $proxy->incrementUsage();

            return $account;
        } catch (\Exception $e) {
            // Если создание аккаунта не удалось, удаляем файлы
            $this->fileService->deleteFiles($filename);
            throw $e;
        }
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

    /**
     * Выбрать множество доступных аккаунтов для задач
     * Возвращает коллекцию уникальных аккаунтов
     */
    public function selectMultipleAvailableAccounts(int $count): \Illuminate\Database\Eloquent\Collection
    {
        return DB::transaction(function () use ($count) {
            // Получаем нужное количество активных аккаунтов с блокировкой
            $accounts = TelegramAccount::query()
                ->active()
                ->orderByUsage()
                ->lockForUpdate()
                ->take($count)
                ->get();

            if ($accounts->isEmpty()) {
                return $accounts;
            }

            // Обновляем статистику для всех выбранных аккаунтов
            $accountIds = $accounts->pluck('id')->toArray();
            TelegramAccount::whereIn('id', $accountIds)->increment('usage_count');
            TelegramAccount::whereIn('id', $accountIds)->update(['last_used_at' => now()]);

            return $accounts;
        });
    }
}