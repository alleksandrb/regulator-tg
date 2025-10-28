<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class CreateApiToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:create-token 
                            {name : The name of the API token}
                            {--user-id= : ID of the user who owns the token}
                            {--user-email= : Email of the user who owns the token}
                            {--description= : Description of the token}
                            {--expires-days= : Number of days until token expires}
                            {--allowed-ips=* : Allowed IP addresses (can be specified multiple times)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API token for external services';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $userId = $this->option('user-id');
        $userEmail = $this->option('user-email');
        $description = $this->option('description');
        $expiresDays = $this->option('expires-days');
        $allowedIps = $this->option('allowed-ips');

        if (!$userId && !$userEmail) {
            $this->error('You must provide either --user-id or --user-email');
            return Command::FAILURE;
        }

        $user = null;
        if ($userId) {
            $user = \App\Models\User::find($userId);
        } elseif ($userEmail) {
            $user = \App\Models\User::where('email', $userEmail)->first();
        }

        if (!$user) {
            $this->error('User not found');
            return Command::FAILURE;
        }

        $expiresAt = null;
        if ($expiresDays) {
            $expiresAt = now()->addDays((int) $expiresDays);
        }

        // Валидация IP-адресов
        $validatedIps = null;
        if (!empty($allowedIps)) {
            $validatedIps = [];
            foreach ($allowedIps as $ip) {
                if ($this->validateIpOrCidr($ip)) {
                    $validatedIps[] = $ip;
                } else {
                    $this->error("Invalid IP address or CIDR: {$ip}");
                    return Command::FAILURE;
                }
            }
        }

        try {
            $apiToken = ApiToken::createForUser($user, $name, $description, $expiresAt, $validatedIps);

            $this->info('API Token created successfully!');
            $this->line('');
            $this->line('<fg=green>Token Details:</>');
            $this->line("Name: {$apiToken->name}");
            $this->line("User: {$user->id} ({$user->email})");
            $this->line("Description: " . ($apiToken->description ?? 'N/A'));
            $this->line("Expires At: " . ($apiToken->expires_at ? $apiToken->expires_at->format('Y-m-d H:i:s') : 'Never'));
            $this->line("Allowed IPs: " . ($apiToken->allowed_ips ? implode(', ', $apiToken->allowed_ips) : 'Any IP'));
            $this->line('');
            $this->line('<fg=yellow>API Token (save this securely):</>');
            $this->line('<fg=red>' . $apiToken->token . '</>');
            $this->line('');
            $this->warn('This token will not be shown again. Make sure to save it securely!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to create API token: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Валидация IP-адреса или CIDR нотации
     */
    private function validateIpOrCidr(string $ip): bool
    {
        // Проверяем обычный IP-адрес
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Проверяем CIDR нотацию
        if (str_contains($ip, '/')) {
            [$subnet, $mask] = explode('/', $ip);
            
            // Проверяем что subnet - валидный IP
            if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
                return false;
            }
            
            // Проверяем маску
            $maskInt = (int) $mask;
            
            // Для IPv4 маска должна быть от 0 до 32
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $maskInt >= 0 && $maskInt <= 32;
            }
            
            // Для IPv6 маска должна быть от 0 до 128
            if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return $maskInt >= 0 && $maskInt <= 128;
            }
        }

        return false;
    }
}
