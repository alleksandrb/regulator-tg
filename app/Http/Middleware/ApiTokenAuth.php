<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Получаем токен из заголовка Authorization
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return $this->unauthorizedResponse('API token is required');
        }

        // Ищем токен в базе данных
        $apiToken = ApiToken::findByToken($token);

        if (!$apiToken) {
            return $this->unauthorizedResponse('Invalid API token');
        }

        // Проверяем валидность токена
        if (!$apiToken->isValid()) {
            return $this->unauthorizedResponse('API token is expired or inactive');
        }

        // Проверяем разрешен ли IP-адрес
        $clientIp = $this->getClientIp($request);
        if (!$apiToken->isIpAllowed($clientIp)) {
            return $this->unauthorizedResponse('Access denied from this IP address');
        }

        // Обновляем время последнего использования
        $apiToken->updateLastUsed();

        // Добавляем токен и пользователя в request для дальнейшего использования
        $request->attributes->set('api_token', $apiToken);
        $request->attributes->set('api_user', $apiToken->user);
        // Позволяет использовать $request->user() для получения владельца токена
        $request->setUserResolver(function () use ($apiToken) {
            return $apiToken->user;
        });
        // Устанавливаем пользователя в глобальный Auth, чтобы Auth::id() работал во всем приложении
        Auth::setUser($apiToken->user);

        return $next($request);
    }

    /**
     * Извлечь токен из запроса
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // Проверяем заголовок Authorization: Bearer {token}
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Проверяем заголовок X-API-Token
        $apiTokenHeader = $request->header('X-API-Token');
        if ($apiTokenHeader) {
            return $apiTokenHeader;
        }

        return null;
    }

    /**
     * Получить IP-адрес клиента с учетом прокси
     */
    private function getClientIp(Request $request): string
    {
        // Проверяем заголовки прокси в порядке приоритета
        $ipHeaders = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Стандартный заголовок прокси
            'HTTP_X_FORWARDED',          // Альтернативный заголовок
            'HTTP_X_CLUSTER_CLIENT_IP',  // Кластер
            'HTTP_FORWARDED_FOR',        // Старый стандарт
            'HTTP_FORWARDED',            // RFC 7239
            'HTTP_CLIENT_IP',            // Некоторые прокси
            'REMOTE_ADDR'                // Прямое подключение
        ];

        foreach ($ipHeaders as $header) {
            $ip = $request->server($header);
            
            if (!empty($ip)) {
                // Если заголовок содержит несколько IP (разделенных запятыми), берем первый
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Проверяем что это валидный IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Если не нашли публичный IP, возвращаем REMOTE_ADDR (может быть приватным)
        return $request->ip() ?? '127.0.0.1';
    }

    /**
     * Вернуть ответ об ошибке авторизации
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }
}
