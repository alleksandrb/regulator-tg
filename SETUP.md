# Regulator TG - Система управления просмотрами Telegram

Веб-приложение на Laravel + Vue.js + Inertia.js для автоматизации просмотров постов в Telegram через очередь задач.

## 🚀 Быстрый старт

### 1. Установка зависимостей

```bash
# Установка PHP зависимостей
composer install

# Установка Node.js зависимостей
npm install --legacy-peer-deps
```

### 2. Настройка базы данных

```bash
# Запуск миграций
php artisan migrate
```

### 3. Сборка фронтенда

```bash
# Для разработки
npm run dev

# Для продакшена
npm run build
```

### 4. Запуск приложения

```bash
# Запуск веб-сервера
php artisan serve

# В отдельном терминале - запуск обработчика очереди
php artisan queue:work
```

Приложение будет доступно по адресу: http://localhost:8000

## 📋 Основной функционал

### Панель управления
- **Добавление просмотров**: Указание ссылки на Telegram пост и количества просмотров
- **Статистика**: Отображение количества активных/неактивных аккаунтов
- **Мониторинг**: Топ используемых аккаунтов с временем последнего использования

### Управление аккаунтами
- **Добавление аккаунтов**: Через API endpoint `/accounts`
- **Мониторинг**: Просмотр статуса и статистики использования аккаунтов
- **Деактивация**: Отключение проблемных аккаунтов

## 🔧 API Endpoints

### POST `/accounts`
Добавление нового Telegram аккаунта

**Параметры:**
```json
{
  "session_data": "base64-encoded string",
  "json_data": {
    "app_id": 123456,
    "app_hash": "your_hash",
    "phone": "+1234567890"
  }
}
```

### GET `/accounts`
Получение списка всех аккаунтов

### PATCH `/accounts/{id}/deactivate`
Деактивация аккаунта

### POST `/dashboard/add-views`
Добавление просмотров для поста

**Параметры:**
```json
{
  "telegram_post_url": "https://t.me/channel/123",
  "views_count": 100
}
```

## 🗄️ Структура базы данных

### Таблица `telegram_accounts`
- `id` - Первичный ключ
- `session_data` - Бинарные данные сессии (BYTEA)
- `json_data` - JSON с данными аккаунта
- `proxy_id` - ID прокси (nullable)
- `usage_count` - Счетчик использований
- `last_used_at` - Время последнего использования
- `is_active` - Статус активности

### Таблица `proxies`
- `id` - Первичный ключ
- `ip` - IP адрес прокси
- `port` - Порт
- `protocol` - Протокол (socks5, http)
- `login` - Логин
- `password` - Пароль
- `usage_count` - Счетчик использований
- `last_used_at` - Время последнего использования
- `is_active` - Статус активности
- `max_accounts` - Максимальное количество аккаунтов на прокси

## ⚙️ Логика работы

### Выбор аккаунтов
1. Выбираются только активные аккаунты (`is_active = true`)
2. Сортировка по `usage_count ASC`, затем `last_used_at ASC NULLS FIRST`
3. Используется `SELECT ... FOR UPDATE SKIP LOCKED` для избежания race conditions
4. После постановки задачи инкрементируется счетчик использований

### Обработка задач
1. Каждый просмотр = отдельная задача в очереди
2. Job `ProcessViewJob` обрабатывает один просмотр
3. Обновляются счетчики использования аккаунта и прокси
4. Логирование всех операций

### Работа с прокси
- Прокси назначается аккаунту при создании
- Автоматическая деактивация нерабочих прокси
- Лимит аккаунтов на прокси (по умолчанию 10)

## 🔐 Аутентификация

Используется Laravel Breeze с поддержкой:
- Регистрации пользователей
- Авторизации
- Восстановления пароля
- Верификации email

## 🏗️ Архитектура

### Backend (Laravel)
- **Контроллеры**: Обработка HTTP запросов
- **Модели**: TelegramAccount, Proxy, User
- **Jobs**: ProcessViewJob для обработки просмотров
- **Services**: ViewService для бизнес-логики
- **Middleware**: HandleInertiaRequests для Inertia.js

### Frontend (Vue.js + Inertia.js)
- **Компоненты**: Переиспользуемые UI элементы
- **Страницы**: Dashboard, Accounts, Auth
- **Layouts**: AuthenticatedLayout, GuestLayout
- **Стили**: Tailwind CSS

### База данных
- **SQLite** (по умолчанию) / **PostgreSQL** (рекомендуется для продакшена)
- **Миграции**: Автоматическое создание таблиц
- **Индексы**: Оптимизированные запросы для выбора аккаунтов

## 🚦 Мониторинг

### Логирование
- Все операции логируются в `storage/logs/laravel.log`
- Отдельное логирование для каждой задачи просмотра
- Предупреждения при отсутствии доступных аккаунтов

### Метрики
- Количество активных/неактивных аккаунтов
- Статистика использования аккаунтов
- Топ наиболее используемых аккаунтов

## 🔧 Настройка для продакшена

### 1. Настройка PostgreSQL
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=regulator_tg
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 2. Настройка очереди Redis
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Supervisor для очереди
```ini
[program:regulator-tg-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/path/to/project
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
stopwaitsecs=3600
```

## 🛠️ Разработка

### Команды для разработки
```bash
# Запуск в режиме разработки с hot reload
npm run dev

# Запуск тестов
php artisan test

# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Создание нового Job
php artisan make:job YourJobName

# Создание новой миграции
php artisan make:migration create_your_table
```

### Добавление новых функций
1. Создайте миграцию для изменений БД
2. Обновите модели
3. Создайте/обновите контроллеры
4. Добавьте маршруты
5. Создайте Vue.js компоненты
6. Обновите тесты

## 📝 Примечания

- **Безопасность**: Все связи в БД делаются на уровне приложения (без foreign key constraints)
- **Масштабируемость**: Горизонтальное масштабирование через Redis и несколько worker'ов
- **Мониторинг**: Логирование всех операций для отладки
- **Гибкость**: Легкое добавление новых типов задач и аккаунтов

## 🚨 Важные замечания

1. **Worker'ы будут в отдельном репозитории** - Laravel только принимает запросы и создает задачи
2. **Никаких жестких связей в БД** - все constraint'ы на уровне приложения
3. **Равномерное распределение нагрузки** - аккаунты выбираются по принципу least-used-first
4. **Отказоустойчивость** - автоматическая деактивация проблемных аккаунтов и прокси
