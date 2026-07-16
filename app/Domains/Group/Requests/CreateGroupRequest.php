<?php

namespace App\Domains\Group\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'type'        => ['sometimes', 'string', 'in:public,private,invite_only'],
            'max_members' => ['sometimes', 'integer', 'min:2', 'max:10000'],
            'members'     => ['sometimes', 'array'],
            'members.*'   => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'اسم المجموعة مطلوب.',
            'name.min'        => 'اسم المجموعة يجب أن يكون حرفين على الأقل.',
            'type.in'         => 'نوع المجموعة يجب أن يكون public أو private أو invite_only.',
            'members.*.exists'=> 'أحد الأعضاء المحددين غير موجود.',
        ];
    }
}