<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramAccount;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CreateTelegramAccountRequest;
use App\Http\Requests\BulkCreateTelegramAccountRequest;
use App\Repositories\TelegramAccountRepository;
use Illuminate\Support\Facades\Log;

class TelegramAccountController extends Controller
{
    public function __construct(
        private TelegramAccountRepository $telegramAccountRepository
    ) {}

    /**
     * Добавить новый Telegram аккаунт
     */ 
    public function store(CreateTelegramAccountRequest $request): JsonResponse
    {
        $request->validated();

        $session = $request->file('session_data')->get();
        $jsonData = $request->file('json_data')->get();
        
        // Извлекаем user_id из JSON
        $jsonDecoded = json_decode($jsonData, true);
        $accountId = isset($jsonDecoded['user_id']) ? (string)$jsonDecoded['user_id'] : null;

        // Проверяем уникальность user_id
        if ($accountId && $this->telegramAccountRepository->existsByUserId($accountId)) {
            return response()->json([
                'success' => false,
                'message' => "Аккаунт с user_id {$accountId} уже существует"
            ], 422);
        }

        $account = $this->telegramAccountRepository->createAccount(
            $session,
            $jsonData,
            $accountId
        );

        return response()->json([
            'success' => true,
            'message' => 'Telegram аккаунт успешно добавлен',
            'account_id' => $account->id
        ]);
    }

    /**
     * Массовое добавление Telegram аккаунтов
     */ 
    public function bulkStore(BulkCreateTelegramAccountRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $createdCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($validated['accounts'] as $accountData) {
            try {
                $session = $accountData['session_data']->get();
                $jsonData = $accountData['json_data']->get();
                
                // Извлекаем user_id из JSON
                $jsonDecoded = json_decode($jsonData, true);
                $userId = isset($jsonDecoded['user_id']) ? (string)$jsonDecoded['user_id'] : null;
                
                // Проверяем уникальность user_id
                if ($userId && $this->telegramAccountRepository->existsByUserId($userId)) {
                    $skippedCount++;
                    $errors[] = [
                        'name' => $accountData['name'] ?? 'unknown',
                        'user_id' => $userId,
                        'error' => "Аккаунт с user_id {$userId} уже существует (пропущен)"
                    ];
                    continue;
                }

                $this->telegramAccountRepository->createAccount($session, $jsonData, $userId);
                $createdCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'name' => $accountData['name'] ?? 'unknown',
                    'user_id' => $userId ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                
                // Логируем ошибку
                Log::error('Failed to create account: ' . $e->getMessage(), [
                    'account_name' => $accountData['name'] ?? 'unknown',
                    'user_id' => $userId ?? 'unknown'
                ]);
            }
        }

        $message = "Обработка завершена. Создано: $createdCount";
        if ($skippedCount > 0) {
            $message .= ", пропущено (дубликаты): $skippedCount";
        }
        if ($failedCount > 0) {
            $message .= ", ошибок: $failedCount";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'created_count' => $createdCount,
            'failed_count' => $failedCount,
            'skipped_count' => $skippedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Получить список аккаунтов
     */
    public function index(): JsonResponse
    {
        $accounts = TelegramAccount::with('proxy')
            ->select(['id', 'account_id', 'proxy_id', 'usage_count', 'last_used_at', 'is_active', 'created_at'])
            ->get();

        return response()->json([
            'success' => true,
            'accounts' => $accounts
        ]);
    }

    /**
     * Деактивировать аккаунт
     */
    public function deactivate(TelegramAccount $account): JsonResponse
    {
        $account->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Аккаунт деактивирован'
        ]);
    }
}
