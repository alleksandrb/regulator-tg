<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\ViewService;

class AddViewRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $viewService = app(ViewService::class);
        $maxAccounts = $viewService->getAvailableAccountsCount();

        return [
            'telegram_post_url' => ['required', 'string', 'url'],
            'views_count' => ['required', 'integer', 'min:1', "max:{$maxAccounts}"],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $viewService = app(ViewService::class);
        $maxAccounts = $viewService->getAvailableAccountsCount();

        return [
            'telegram_post_url.required' => 'Ссылка на пост обязательна',
            'telegram_post_url.url' => 'Ссылка должна быть валидным URL',
            'views_count.required' => 'Количество просмотров обязательно',
            'views_count.integer' => 'Количество просмотров должно быть числом',
            'views_count.min' => 'Минимальное количество просмотров: 1',
            'views_count.max' => "Максимальное количество просмотров: {$maxAccounts} (доступно аккаунтов)",
        ];
    }
}
