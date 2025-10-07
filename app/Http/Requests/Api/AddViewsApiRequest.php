<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Services\ViewService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddViewsApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Авторизация проходит через middleware ApiTokenAuth
        return true;
    }

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
            'telegram_post_url' => [
                'required',
                'string',
                'url',
                'regex:/^https?:\/\/(t\.me|telegram\.me)\//'
            ],
            'views_count' => [
                'required',
                'integer',
                'min:1',
                "max:{$maxAccounts}"
            ],
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
            'telegram_post_url.required' => 'Telegram post URL is required',
            'telegram_post_url.url' => 'Telegram post URL must be a valid URL',
            'telegram_post_url.regex' => 'URL must be a valid Telegram link (t.me or telegram.me)',
            'views_count.required' => 'Views count is required',
            'views_count.integer' => 'Views count must be an integer',
            'views_count.min' => 'Minimum views count is 1',
            'views_count.max' => "Maximum views count is {$maxAccounts} (available accounts)",
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'Validation Error',
                'message' => 'The given data was invalid',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
