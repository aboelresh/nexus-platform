<?php

namespace App\Domains\Group\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'يجب تحديد المستخدم.',
            'user_id.exists'   => 'المستخدم غير موجود.',
        ];
    }
}