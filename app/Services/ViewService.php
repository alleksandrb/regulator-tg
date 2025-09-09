<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelegramAccount;
use App\Models\ViewTask;
use App\Repositories\TelegramAccountRepository;
use App\Services\QueueService;
use Illuminate\Support\Facades\Auth;
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
        // Сохраняем задачу в базу данных
        ViewTask::create([
            'telegram_post_url' => $telegramPostUrl,
            'views_count' => $viewsCount,
            'user_id' => Auth::id(),
        ]);
        
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

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
        ];
    }

    /**
     * Получить список добавленных просмотров с пагинацией
     */
    public function getViewTasks(int $perPage = 10): \Illuminate\Pagination\LengthAwarePaginator
    {
        return ViewTask::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['id', 'telegram_post_url', 'views_count', 'user_id', 'created_at']);
    }
}
