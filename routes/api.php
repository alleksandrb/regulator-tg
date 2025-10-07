<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ViewsController;
use App\Http\Middleware\ApiTokenAuth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->middleware([ApiTokenAuth::class])->group(function () {
    // Проверка токена
    Route::get('/token/check', [ViewsController::class, 'checkToken']);
    
    // Статистика
    Route::get('/stats', [ViewsController::class, 'getStats']);
    
    // Добавление просмотров
    Route::post('/views', [ViewsController::class, 'addViews']);
});
