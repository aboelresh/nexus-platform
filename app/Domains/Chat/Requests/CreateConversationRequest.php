<?php

namespace App\Domains\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'        => ['required', 'string', 'in:direct,group'],
            'user_id'     => ['required_if:type,direct', 'integer', 'exists:users,id'],
            'name'        => ['required_if:type,group', 'string', 'min:2', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'members'     => ['sometimes', 'array', 'min:1'],
            'members.*'   => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'           => 'نوع المحادثة يجب أن يكون direct أو group.',
            'user_id.required_if' => 'يجب تحديد المستخدم للمحادثة المباشرة.',
            'user_id.exists'    => 'المستخدم غير موجود.',
            'name.required_if'  => 'اسم المجموعة مطلوب.',
            'members.*.exists'  => 'أحد الأعضاء المحددين غير موجود.',
        ];
    }
}