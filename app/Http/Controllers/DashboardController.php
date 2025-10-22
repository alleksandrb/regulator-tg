<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddViewRequest;
use App\Services\ViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ViewService $viewService
    ) {}

    /**
     * Показать панель управления
     */
    public function index(Request $request): Response
    {
        $stats = $this->viewService->getAccountsStats();
        $telegramPostUrlFilter = (string) $request->query('telegram_post_url', '');
        $viewTasks = $this->viewService->getViewTasks(10, $telegramPostUrlFilter)->withQueryString();
        $availableAccountsCount = $this->viewService->getAvailableAccountsCount();
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'viewTasks' => $viewTasks,
            'availableAccountsCount' => $availableAccountsCount,
            'telegramPostUrlFilter' => $telegramPostUrlFilter,
        ]);
    }

    /**
     * Добавить просмотры для поста
     */
    public function addViews(AddViewRequest $request): JsonResponse
    {
        $request->validated();

        try {
            $this->viewService->addViews(
                $request->input('telegram_post_url'),
                $request->input('views_count')
            );

            return response()->json([
                'success' => true,
                'message' => "Задания взято в работу",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Получить список просмотров (AJAX)
     */
    public function getViewTasks(Request $request): JsonResponse
    {
        $telegramPostUrlFilter = (string) $request->query('telegram_post_url', '');
        $viewTasks = $this->viewService->getViewTasks(10, $telegramPostUrlFilter)
            ->withQueryString()
            ->withPath(route('dashboard'));
        
        return response()->json([
            'success' => true,
            'viewTasks' => $viewTasks
        ]);
    }
}
