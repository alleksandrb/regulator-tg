<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Proxy;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CreateProxyRequest;
use App\Services\ProxyService;

class ProxyController extends Controller
{
    public function __construct(
        private ProxyService $proxyService
    ) {}

    /**
     * Добавить новые прокси из файла
     */ 
    public function store(CreateProxyRequest $request): JsonResponse
    {
        $request->validated();

        $proxyFile = $request->file('proxy_file');
        $fileContent = $proxyFile->get();
        
        try {
            // Используем сервис для массовой вставки прокси
            $proxiesAdded = $this->proxyService->createProxyFromFile($fileContent);

            return response()->json([
                'success' => true,
                'message' => "Успешно добавлено прокси: {$proxiesAdded}",
                'proxies_added' => $proxiesAdded
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке файла: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Получить список прокси с пагинацией
     */
    public function index(): JsonResponse
    {
        $proxies = Proxy::select([
            'id', 
            'ip', 
            'port', 
            'protocol', 
            'login', 
            'usage_count', 
            'last_used_at', 
            'is_active', 
            'max_accounts',
            'created_at'
        ])
        ->withCount('telegramAccounts')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return response()->json([
            'success' => true,
            'proxies' => $proxies
        ]);
    }

    /**
     * Деактивировать прокси
     */
    public function deactivate(Proxy $proxy): JsonResponse
    {
        $proxy->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Прокси деактивирован'
        ]);
    }

    /**
     * Активировать прокси
     */
    public function activate(Proxy $proxy): JsonResponse
    {
        $proxy->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Прокси активирован'
        ]);
    }
}
