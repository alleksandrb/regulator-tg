<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramAccount;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CreateTelegramAccountRequest;
use App\Repositories\TelegramAccountRepository;

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

        $session = base64_encode($request->file('session_data')->get());
        $jsonData = $request->file('json_data')->get();

        $account = $this->telegramAccountRepository->createAccount(
            $session,
            $jsonData,
        );

        return response()->json([
            'success' => true,
            'message' => 'Telegram аккаунт успешно добавлен',
            'account_id' => $account->id
        ]);

    }

    /**
     * Получить список аккаунтов
     */
    public function index(): JsonResponse
    {
        $accounts = TelegramAccount::with('proxy')
            ->select(['id', 'proxy_id', 'usage_count', 'last_used_at', 'is_active', 'created_at'])
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
