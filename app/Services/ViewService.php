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
        // Проверяем количество доступных аккаунтов перед постановкой в очередь
        $availableAccountsCount = $this->getAvailableAccountsCount();
        
        if ($availableAccountsCount === 0) {
            throw new \Exception('Нет доступных аккаунтов для добавления просмотров');
        }
        
        if ($viewsCount > $availableAccountsCount) {
            throw new \Exception("Запрошено просмотров: {$viewsCount}, доступно аккаунтов: {$availableAccountsCount}");
        }

        // Получаем все необходимые аккаунты одним запросом
        $accounts = $this->accountRepository->selectMultipleAvailableAccounts($viewsCount);
        
        if ($accounts->isEmpty()) {
            throw new \Exception('Не удалось получить аккаунты для постановки задач в очередь');
        }
        
        $actualAccountsCount = $accounts->count();
        if ($actualAccountsCount < $viewsCount) {
            Log::warning("Requested {$viewsCount} accounts, but only {$actualAccountsCount} available", [
                'telegram_post_url' => $telegramPostUrl,
                'requested_views' => $viewsCount,
                'actual_accounts' => $actualAccountsCount
            ]);
        }

        // Сохраняем задачу в базу данных с фактическим количеством просмотров
        ViewTask::create([
            'telegram_post_url' => $telegramPostUrl,
            'views_count' => $actualAccountsCount, // Сохраняем фактическое количество
            'user_id' => Auth::id(),
        ]);
        
        $successfulTasks = 0;
        $failedTasks = 0;
        
        for ($i = 0; $i < 2; $i++) {
        // Ставим задачи в очередь для каждого полученного аккаунта
            foreach ($accounts as $account) {
                try {
                    $this->queueService->addViewIncrementTask(
                        $account,
                        $telegramPostUrl
                    );
                    $successfulTasks++;
                } catch (\Exception $e) {
                    $failedTasks++;
                    Log::error("Failed to add view increment task: " . $e->getMessage(), [
                        'telegram_post_url' => $telegramPostUrl,
                        'account_id' => $account->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Логируем результат
        Log::info("View tasks processing completed", [
            'telegram_post_url' => $telegramPostUrl,
            'requested_views' => $viewsCount,
            'actual_accounts_selected' => $actualAccountsCount,
            'successful_tasks' => $successfulTasks,
            'failed_tasks' => $failedTasks,
            'available_accounts_at_start' => $availableAccountsCount
        ]);
        
        // Если не удалось поставить ни одной задачи, выбрасываем исключение
        if ($successfulTasks === 0) {
            throw new \Exception('Не удалось поставить ни одной задачи в очередь');
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
     * Получить количество доступных аккаунтов
     */
    public function getAvailableAccountsCount(): int
    {
        return TelegramAccount::active()->count();
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
