<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TelegramAccountController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    
    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Панель управления
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/add-views', [DashboardController::class, 'addViews'])->name('dashboard.add-views');
    
    // Страница управления аккаунтами
    Route::get('/accounts/manage', function () {
        return Inertia::render('Accounts');
    })->name('accounts.manage');
    
    // API для управления аккаунтами
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::post('/', [TelegramAccountController::class, 'store'])->name('store');
        Route::post('/bulk', [TelegramAccountController::class, 'bulkStore'])->name('bulk-store');
        Route::get('/', [TelegramAccountController::class, 'index'])->name('index');
        Route::patch('/{account}/deactivate', [TelegramAccountController::class, 'deactivate'])->name('deactivate');
    });
    
    // Страница управления прокси
    Route::get('/proxies/manage', function () {
        return Inertia::render('Proxies');
    })->name('proxies.manage');
    
    // API для управления прокси
    Route::prefix('proxies')->name('proxies.')->group(function () {
        Route::post('/', [ProxyController::class, 'store'])->name('store');
        Route::get('/', [ProxyController::class, 'index'])->name('index');
        Route::patch('/{proxy}/deactivate', [ProxyController::class, 'deactivate'])->name('deactivate');
        Route::patch('/{proxy}/activate', [ProxyController::class, 'activate'])->name('activate');
    });
    
    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Управление пользователями
    Route::get('/users/manage', [UserManagementController::class, 'index'])->name('users.manage');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});

require __DIR__.'/auth.php';
