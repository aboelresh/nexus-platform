<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $visibilityOptions = 'in:everyone,contacts,nobody';

        return [
            'last_seen'       => ['sometimes', 'string', $visibilityOptions],
            'profile_photo'   => ['sometimes', 'string', $visibilityOptions],
            'bio'             => ['sometimes', 'string', $visibilityOptions],
            'groups'          => ['sometimes', 'string', $visibilityOptions],
            'direct_messages' => ['sometimes', 'string', 'in:everyone,contacts,nobody'],
        ];
    }
}