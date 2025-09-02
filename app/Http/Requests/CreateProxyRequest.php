<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProxyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'proxy_file' => 'required|file|mimes:txt|max:2048',
        ];
    }
    
    public function messages(): array
    {
        return [
            'proxy_file.required' => 'Необходимо выбрать файл с прокси',
            'proxy_file.file' => 'Загруженный файл должен быть валидным',
            'proxy_file.mimes' => 'Файл должен быть в формате TXT',
            'proxy_file.max' => 'Размер файла не должен превышать 2MB',
        ];
    }
}
