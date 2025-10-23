<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TelegramAccount;
use App\Services\ProxyService;
use App\Factories\ProxyDTOFactory;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CreateTelegramAccountRequest;
use App\Http\Requests\BulkCreateTelegramAccountRequest;
use App\Repositories\TelegramAccountRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Jobs\ProcessTelegramAccountsUpload;
use App\Models\AccountImport;
use Illuminate\Support\Facades\Auth;

class TelegramAccountController extends Controller
{
    public function __construct(
        private TelegramAccountRepository $telegramAccountRepository,
        private ProxyService $proxyService,
        private ProxyDTOFactory $proxyDTOFactory
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

        // Создаем уникальную директорию для батча загрузки
        $batchId = (string) Str::orderedUuid();
        $batchDir = 'telegram/uploads/' . $batchId;
        $accountsDir = $batchDir . '/accounts';

        // Сохраняем прокси файл, если передан
        if ($request->hasFile('proxy_file')) {
            try {
                $request->file('proxy_file')->storeAs($batchDir, 'proxy.txt', 'local');
            } catch (\Throwable $e) {
                Log::error('Failed to store proxy file for upload batch: ' . $e->getMessage(), [
                    'batch_id' => $batchId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось сохранить файл прокси',
                ], 422);
            }
        }

        // Сохраняем файлы аккаунтов в порядке следования, чтобы воспроизвести их в воркере
        foreach ($validated['accounts'] as $index => $accountData) {
            try {
                $accountData['session_data']->storeAs($accountsDir, $index . '.session', 'local');
                $accountData['json_data']->storeAs($accountsDir, $index . '.json', 'local');
            } catch (\Throwable $e) {
                Log::error('Failed to store account files for upload batch: ' . $e->getMessage(), [
                    'batch_id' => $batchId,
                    'index' => $index,
                ]);
                // Чистим уже сохраненные файлы батча
                try { Storage::disk('local')->deleteDirectory($batchDir); } catch (\Throwable $t) {}
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось сохранить файлы аккаунтов',
                ], 422);
            }
        }

        // Ставим задачу на обработку в очередь и быстро отвечаем клиенту
        // Создадим запись об импорте
        $import = AccountImport::query()->create([
            'user_id' => Auth::id(),
            'batch_id' => $batchId,
            'total_count' => count($validated['accounts']),
            'status' => 'queued',
        ]);

        ProcessTelegramAccountsUpload::dispatch($batchDir)->onQueue(config('queue.connections.database.queue', 'default'));

        return response()->json([
            'success' => true,
            'message' => 'Задача взята в работу',
            'batch_id' => $batchId,
            'import_id' => $import->id,
        ], 202);
    }

    /**
     * Получить список аккаунтов
     */
    public function index(): JsonResponse
    {
        $imports = \App\Models\AccountImport::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'imports' => $imports
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
