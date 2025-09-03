<?php

declare(strict_types=1);

namespace App\Factories;

use App\DTOs\ProxyDTO;
use App\Models\Proxy;

class ProxyDTOFactory
{
    /**
     * @return ProxyDTO[]
     * @throws \InvalidArgumentException
     */
    public function createFromFile(string $proxyFile): array
    {
        $proxyLines = array_filter(explode("\n", trim($proxyFile)), fn($line) => !empty(trim($line)));
        $proxies = [];

        foreach ($proxyLines as $lineNumber => $proxyLine) {
            $proxyLine = trim($proxyLine);
            
            if (empty($proxyLine)) {
                continue;
            }

            try {
                $proxyData = explode(";", $proxyLine);
                $proxy = [];

                foreach ($proxyData as $data) {
                    $parts = explode(":", $data, 2);
                    if (count($parts) !== 2) {
                        throw new \InvalidArgumentException("Неверный формат данных в строке " . ($lineNumber + 1) . ": {$data}");
                    }
                    $proxy[trim($parts[0])] = trim($parts[1]);
                }

                // Валидация обязательных полей
                $requiredFields = ['ip', 'port', 'protocol', 'login', 'password'];
                foreach ($requiredFields as $field) {
                    if (!isset($proxy[$field]) || empty($proxy[$field])) {
                        throw new \InvalidArgumentException("Отсутствует обязательное поле '{$field}' в строке " . ($lineNumber + 1));
                    }
                }

                // Валидация порта
                if (!is_numeric($proxy['port']) || (int)$proxy['port'] < 1 || (int)$proxy['port'] > 65535) {
                    throw new \InvalidArgumentException("Неверный порт в строке " . ($lineNumber + 1) . ": {$proxy['port']}");
                }

                // Валидация IP
                if (!filter_var($proxy['ip'], FILTER_VALIDATE_IP)) {
                    throw new \InvalidArgumentException("Неверный IP адрес в строке " . ($lineNumber + 1) . ": {$proxy['ip']}");
                }

                $proxies[] = new ProxyDTO(
                    ip: $proxy['ip'],
                    port: (int)$proxy['port'],
                    protocol: strtolower($proxy['protocol']),
                    login: $proxy['login'],
                    password: $proxy['password'],
                    refreshLink: $proxy['refresh_link'] ?? '',
                    name: $proxy['name'] ?? "Proxy {$proxy['ip']}:{$proxy['port']}",
                );
            } catch (\Exception $e) {
                throw new \InvalidArgumentException("Ошибка обработки строки " . ($lineNumber + 1) . ": " . $e->getMessage());
            }
        }

        if (empty($proxies)) {
            throw new \InvalidArgumentException("Файл не содержит валидных прокси");
        }

        return $proxies;
    }

    public function createFromModel(Proxy $proxy): ProxyDTO
    {
        return new ProxyDTO(
            id: $proxy->id,
            ip: $proxy->ip,
            port: $proxy->port,
            protocol: $proxy->protocol,
            login: $proxy->login,
            password: $proxy->password,
            refreshLink: $proxy->refresh_link ?? '',
            name: $proxy->name ?? '',
            usageCount: $proxy->usage_count,
            isActive: $proxy->is_active,
            maxAccounts: $proxy->max_accounts,
            lastUsedAt: $proxy->last_used_at?->toISOString(),
            createdAt: $proxy->created_at?->toISOString(),
            updatedAt: $proxy->updated_at?->toISOString(),
        );
    }
}