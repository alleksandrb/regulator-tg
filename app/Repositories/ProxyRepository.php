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
        return Proxy::orderBy('usage_count', 'asc')
            ->where('is_active', true)
            ->first();
    }
    
}