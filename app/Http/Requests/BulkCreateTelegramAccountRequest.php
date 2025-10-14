<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateTelegramAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'accounts' => 'required|array|min:1',
            'accounts.*.session_data' => 'required|file|max:10240',
            'accounts.*.json_data' => 'required|file|mimes:json,txt|max:1024',
            'accounts.*.name' => 'sometimes|string|max:255',
            'proxy_file' => 'sometimes|file|mimes:txt|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'accounts.required' => 'Необходимо загрузить файлы аккаунтов',
            'accounts.min' => 'Необходимо загрузить хотя бы один аккаунт',
            'accounts.*.session_data.required' => 'Для каждого аккаунта требуется session файл',
            'accounts.*.session_data.file' => 'Session данные должны быть файлом',
            'accounts.*.session_data.max' => 'Размер session файла не должен превышать 10MB',
            'accounts.*.json_data.required' => 'Для каждого аккаунта требуется JSON файл',
            'accounts.*.json_data.file' => 'JSON данные должны быть файлом',
            'accounts.*.json_data.mimes' => 'JSON файл должен иметь расширение .json или .txt',
            'accounts.*.json_data.max' => 'Размер JSON файла не должен превышать 1MB',
            'proxy_file.file' => 'Файл прокси должен быть валидным файлом',
            'proxy_file.mimes' => 'Файл прокси должен быть в формате TXT',
            'proxy_file.max' => 'Размер файла прокси не должен превышать 2MB',
        ];
    }
}
