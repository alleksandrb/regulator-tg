<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Proxy;
use App\DTOs\ProxyDTO;
use App\Factories\ProxyDTOFactory;

class ProxyRepository
{
    public function __construct(
        private ProxyDTOFactory $proxyDTOFactory
    ) {}

    public function createProxy(ProxyDTO $proxy): ProxyDTO
    {
        $proxyModel =  Proxy::query()->updateOrCreate([
            'ip' => $proxy->ip,
            'port' => $proxy->port,
            'login' => $proxy->login,
            'password' => $proxy->password,
        ],[
            'protocol' => $proxy->protocol,
            'refresh_link' => $proxy->refreshLink,
            'name' => $proxy->name,
        ]);
        
        return $this->proxyDTOFactory->createFromModel($proxyModel);

    }

    public function getProxyWithMinUsage(): Proxy
    {
        // Сначала получаем все активные прокси с подсчетом аккаунтов
        $proxy = Proxy::query()
            ->where('is_active', true)
            ->withCount('telegramAccounts')
            ->get()
            ->filter(function ($proxy) {
                // Фильтруем прокси, которые не достигли лимита
                return $proxy->max_accounts === null || 
                       $proxy->max_accounts === 0 || 
                       $proxy->telegram_accounts_count < $proxy->max_accounts;
            })
            ->sortBy([
                ['telegram_accounts_count', 'asc'],
                ['id', 'asc']
            ])
            ->first();

        if (!$proxy) {
            throw new \Exception('Нет доступных прокси для привязки нового аккаунта');
        }

        return $proxy;
    }
    
}