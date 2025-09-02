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
}