<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddViewsApiRequest;
use App\Models\ApiToken;
use App\Services\ViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ViewsController extends Controller
{
    public function __construct(
        private ViewService $viewService
    ) {}

    /**
     * Добавить просмотры для поста через API
     */
    public function addViews(AddViewsApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->viewService->addViews(
                $validated['telegram_post_url'],
                $validated['views_count']
            );

            // Получаем информацию о токене для логирования
            /** @var ApiToken $apiToken */
            $apiToken = $request->attributes->get('api_token');

            return response()->json([
                'success' => true,
                'message' => 'Views task has been queued successfully',
                'data' => [
                    'telegram_post_url' => $validated['telegram_post_url'],
                    'views_count' => $validated['views_count'],
                    'requested_by' => $apiToken->name,
                    'requested_at' => now()->toISOString(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Bad Request',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Получить статистику доступных аккаунтов
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->viewService->getAccountsStats();
            $availableAccountsCount = $this->viewService->getAvailableAccountsCount();

            return response()->json([
                'success' => true,
                'data' => [
                    'available_accounts' => $availableAccountsCount,
                    'stats' => $stats,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Unable to fetch stats',
            ], 500);
        }
    }

    /**
     * Проверить статус API токена
     */
    public function checkToken(Request $request): JsonResponse
    {
        /** @var ApiToken $apiToken */
        $apiToken = $request->attributes->get('api_token');

        return response()->json([
            'success' => true,
            'data' => [
                'token_name' => $apiToken->name,
                'token_description' => $apiToken->description,
                'last_used_at' => $apiToken->last_used_at?->toISOString(),
                'expires_at' => $apiToken->expires_at?->toISOString(),
                'is_active' => $apiToken->is_active,
            ]
        ], 200);
    }
}
