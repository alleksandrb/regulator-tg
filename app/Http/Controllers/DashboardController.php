<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddViewRequest;
use App\Services\ViewService;
use Illuminate\Http\JsonResponse;
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
    public function index(): Response
    {
        $stats = $this->viewService->getAccountsStats();
        $viewTasks = $this->viewService->getViewTasks(10);
        
        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'viewTasks' => $viewTasks
        ]);
    }

    /**
     * Добавить просмотры для поста
     */
    public function addViews(AddViewRequest $request): JsonResponse
    {
        $request->validated();

        $this->viewService->addViews(
            $request->input('telegram_post_url'),
            $request->input('views_count')
        );

        return response()->json([
            'success' => true,
            'message' => "Задания взято в работу",
        ]);
    }

    /**
     * Получить список просмотров (AJAX)
     */
    public function getViewTasks(): JsonResponse
    {
        $viewTasks = $this->viewService->getViewTasks(10);
        
        return response()->json([
            'success' => true,
            'viewTasks' => $viewTasks
        ]);
    }
}
