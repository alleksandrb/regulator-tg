<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Proxy;

class ProxyRepositories
{
    public function createProxy(string $ip, int $port, string $protocol, string $login, string $password): Proxy
    {
        return Proxy::create([
            'ip' => $ip,
            'port' => $port,
            'protocol' => $protocol,
        ]);
    }

    public function getProxyWithMinUsage(): Proxy
    {
        return Proxy::orderBy('usage_count', 'asc')
            ->where('is_active', true)
            ->first();
    }
    
}