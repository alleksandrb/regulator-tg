<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelegramAccount;
use Illuminate\Support\Facades\Redis;

class QueueService
{
    private const QUEUE_NAME_VIEW_INCREMENT = 'view-increment-queue';

    public function addViewIncrementTask(TelegramAccount $account, string $telegramPostUrl): void
    {
        $jsonPayload = json_encode([
            'account_id' => base64_encode($account->getSession()),
            'account_json_data' => $account->getJsonData(),
            'proxy' => $account->proxy()->first()->toArray(),
            'telegram_post_url' => $telegramPostUrl
        ]);

        Redis::lpush(self::QUEUE_NAME_VIEW_INCREMENT, $jsonPayload);
    }
}
