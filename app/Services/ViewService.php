<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessViewJob;
use App\Models\TelegramAccount;
use App\Repositories\TelegramAccountRepository;
use App\Services\QueueService;
use Illuminate\Support\Facades\Log;

class ViewService
{

    public function __construct(
        private TelegramAccountRepository $accountRepository,
        private QueueService $queueService
    ) {}

    /**
     * Добавить просмотры для поста
     */
    public function addViews(string $telegramPostUrl, int $viewsCount): void
    {
        
        for ($i = 0; $i < $viewsCount; $i++) {
            $account = $this->accountRepository->selectAvailableAccount();
            
            if (!$account) {
                Log::warning("No available accounts for view {$i}");

                continue;
            }

            $this->queueService->addViewIncrementTask(
                $account,
                $telegramPostUrl
            );

        }

    }


    /**
     * Получить статистику аккаунтов
     */
    public function getAccountsStats(): array
    {
        $total = TelegramAccount::count();
        $active = TelegramAccount::active()->count();
        $inactive = $total - $active;
        
        $topUsed = TelegramAccount::active()
            ->orderBy('usage_count', 'desc')
            ->take(5)
            ->get(['id', 'usage_count', 'last_used_at']);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'top_used' => $topUsed
        ];
    }
}
