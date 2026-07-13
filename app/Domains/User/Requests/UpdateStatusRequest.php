<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'custom_status'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'presence_status' => ['sometimes', 'string', 'in:online,offline,away,busy'],
        ];
    }
}