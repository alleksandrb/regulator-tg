<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTelegramAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'session_data' => 'required|file|max:10240',
            'json_data' => 'required|file|mimes:json,txt|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'session_data.required' => 'Требуется session файл',
            'session_data.file' => 'Session данные должны быть файлом',
            'session_data.max' => 'Размер session файла не должен превышать 10MB',
            'json_data.required' => 'Требуется JSON файл',
            'json_data.file' => 'JSON данные должны быть файлом',
            'json_data.mimes' => 'JSON файл должен иметь расширение .json или .txt',
            'json_data.max' => 'Размер JSON файла не должен превышать 1MB',
        ];
    }
}