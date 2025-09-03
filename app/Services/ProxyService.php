<?php

declare(strict_types=1);

namespace App\Services;

use App\Factories\ProxyDTOFactory;
use App\Repositories\ProxyRepository;

class ProxyService
{
    public function __construct(
        private ProxyDTOFactory $proxyDTOFactory,
        private ProxyRepository $proxyRepository
    ) {}

    public function createProxyFromFile(string $proxyFile): int
    {
        $proxies = $this->proxyDTOFactory->createFromFile($proxyFile);
        foreach ($proxies as $proxy) {
            $this->proxyRepository->createProxy($proxy);
        }
        return count($proxies);
    }
}