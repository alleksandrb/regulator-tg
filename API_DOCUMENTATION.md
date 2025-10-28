# API для накрутки просмотров Telegram

Данное API позволяет внешним сервисам отправлять запросы на накрутку просмотров постов в Telegram.

## Авторизация

Для использования API необходим токен авторизации. Токен можно получить у администратора системы.

Каждый API токен теперь привязан к конкретному пользователю системы. Все запросы, выполненные с использованием токена, учитываются как действия этого пользователя.

### Способы передачи токена:

1. **Bearer Token в заголовке Authorization:**
```
Authorization: Bearer YOUR_API_TOKEN
```

2. **Заголовок X-API-Token:**
```
X-API-Token: YOUR_API_TOKEN
```

### Ограничения по IP-адресам

Каждый API токен может быть ограничен определенными IP-адресами для повышения безопасности. Если для токена настроены разрешенные IP-адреса, запросы будут приниматься только с этих адресов.

**Поддерживаемые форматы IP:**
- Отдельные IPv4 адреса: `192.168.1.100`
- IPv4 подсети в CIDR нотации: `192.168.1.0/24`
- IPv6 адреса: `2001:db8::1`
- IPv6 подсети: `2001:db8::/32`

**Примеры ошибок при неразрешенном IP:**
```json
{
    "success": false,
    "error": "Unauthorized",
    "message": "Access denied from this IP address"
}
```

## Базовый URL

```
https://your-domain.com/api/v1
```

## Эндпоинты

### 1. Проверка токена

**GET** `/token/check`

Проверяет валидность API токена и возвращает информацию о нем.

**Пример ответа:**
```json
{
    "success": true,
    "data": {
        "token_name": "External Service 1",
        "token_description": "Token for partner integration",
        "last_used_at": "2025-10-05T20:30:00.000000Z",
        "expires_at": null,
        "is_active": true
    }
}
```

### 2. Получение статистики

**GET** `/stats`

Возвращает информацию о доступных аккаунтах для накрутки просмотров.

**Пример ответа:**
```json
{
    "success": true,
    "data": {
        "available_accounts": 150,
        "stats": {
            "total_accounts": 200,
            "active_accounts": 180,
            "available_accounts": 150,
            "busy_accounts": 30
        }
    }
}
```

### 3. Добавление просмотров

**POST** `/views`

Добавляет просмотры для указанного поста Telegram.

**Параметры запроса:**
```json
{
    "telegram_post_url": "https://t.me/channel_name/123",
    "views_count": 50
}
```

**Валидация:**
- `telegram_post_url` - обязательный, должен быть валидной ссылкой на Telegram (t.me или telegram.me)
- `views_count` - обязательный, целое число от 1 до количества доступных аккаунтов

**Пример успешного ответа:**
```json
{
    "success": true,
    "message": "Views task has been queued successfully",
    "data": {
        "telegram_post_url": "https://t.me/channel_name/123",
        "views_count": 50,
        "requested_by": "External Service 1",
        "requested_at": "2025-10-05T20:30:00.000000Z"
    }
}
```

**Пример ошибки валидации:**
```json
{
    "success": false,
    "error": "Validation Error",
    "message": "The given data was invalid",
    "errors": {
        "views_count": [
            "Maximum views count is 150 (available accounts)"
        ]
    }
}
```

## Коды ошибок

- **200** - Успешный запрос
- **401** - Ошибка авторизации (неверный или отсутствующий токен)
- **422** - Ошибка валидации данных
- **500** - Внутренняя ошибка сервера

## Примеры использования

### cURL

```bash
# Проверка токена
curl -X GET "https://your-domain.com/api/v1/token/check" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# Получение статистики
curl -X GET "https://your-domain.com/api/v1/stats" \
  -H "Authorization: Bearer YOUR_API_TOKEN"

# Добавление просмотров
curl -X POST "https://your-domain.com/api/v1/views" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "telegram_post_url": "https://t.me/channel_name/123",
    "views_count": 50
  }'
```

### PHP

```php
<?php

$apiToken = 'YOUR_API_TOKEN';
$baseUrl = 'https://your-domain.com/api/v1';

// Добавление просмотров
$data = [
    'telegram_post_url' => 'https://t.me/channel_name/123',
    'views_count' => 50
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/views');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200 && $result['success']) {
    echo "Views added successfully!\n";
} else {
    echo "Error: " . $result['message'] . "\n";
}
?>
```

### Python

```python
import requests
import json

API_TOKEN = 'YOUR_API_TOKEN'
BASE_URL = 'https://your-domain.com/api/v1'

headers = {
    'Authorization': f'Bearer {API_TOKEN}',
    'Content-Type': 'application/json'
}

# Добавление просмотров
data = {
    'telegram_post_url': 'https://t.me/channel_name/123',
    'views_count': 50
}

response = requests.post(f'{BASE_URL}/views', headers=headers, json=data)

if response.status_code == 200:
    result = response.json()
    if result['success']:
        print("Views added successfully!")
    else:
        print(f"Error: {result['message']}")
else:
    print(f"HTTP Error: {response.status_code}")
```

## Создание API токена

Для создания нового API токена используйте команду:

```bash
php artisan api:create-token "Service Name" --user-email=user@example.com --description="Description of the service" --expires-days=365 --allowed-ips=192.168.1.100 --allowed-ips=10.0.0.0/8
```

Параметры:
- `name` - название токена (обязательный)
- `--user-id` или `--user-email` - владелец токена (обязательно указать один из вариантов)
- `--description` - описание токена (опционально)
- `--expires-days` - количество дней до истечения токена (опционально)
- `--allowed-ips` - разрешенные IP-адреса или подсети (можно указать несколько раз, опционально)

**Примеры создания токенов:**

```bash
# Токен без ограничений по IP (доступ с любых адресов)
php artisan api:create-token "Public API" --user-id=1

# Токен с ограничением на один IP
php artisan api:create-token "Office API" --user-email=office@example.com --allowed-ips=203.0.113.10

# Токен с ограничением на подсеть
php artisan api:create-token "Internal API" --user-id=2 --allowed-ips=192.168.1.0/24

# Токен с несколькими разрешенными IP/подсетями
php artisan api:create-token "Multi-location API" \
  --user-email=multi@example.com \
  --allowed-ips=203.0.113.10 \
  --allowed-ips=198.51.100.0/24 \
  --allowed-ips=2001:db8::1
```

## Безопасность

1. **Храните токены в безопасности** - не передавайте их через незащищенные каналы
2. **Используйте HTTPS** - всегда используйте защищенное соединение
3. **Ротация токенов** - регулярно обновляйте токены
4. **Мониторинг** - отслеживайте использование токенов через поле `last_used_at`
5. **Ограничения по IP** - используйте whitelist IP-адресов для критически важных токенов
6. **Принцип минимальных привилегий** - создавайте токены с максимально строгими ограничениями

### Рекомендации по настройке IP-ограничений:

- **Для продакшн серверов**: указывайте точные IP-адреса серверов
- **Для офисных сетей**: используйте CIDR нотацию для подсетей
- **Для разработки**: можно не указывать ограничения или использовать широкие подсети
- **Для CDN/прокси**: учитывайте IP-адреса промежуточных серверов

### Обработка прокси и балансировщиков:

Система автоматически определяет реальный IP клиента через заголовки:
- `CF-Connecting-IP` (Cloudflare)
- `X-Forwarded-For` (стандартный заголовок)
- `X-Real-IP` и другие популярные заголовки прокси
