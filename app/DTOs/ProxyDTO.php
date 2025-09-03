<?php

declare(strict_types=1);

namespace App\DTOs;

class ProxyDTO
{
    public function __construct(
        public ?int $id = null,
        public string $ip = '',
        public int $port = 0,
        public string $protocol = '',
        public string $login = '',
        public string $password = '',
        public string $refreshLink = '',
        public string $name = '',
        public ?int $usageCount = null,
        public ?bool $isActive = null,
        public ?int $maxAccounts = null,
        public ?string $lastUsedAt = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}
}