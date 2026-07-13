<?php

namespace App\Domains\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'min:2', 'max:100'],
            'username'      => ['sometimes', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('users', 'username')->ignore($this->user()->id)],
            'bio'           => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'timezone'      => ['sometimes', 'string', 'timezone'],
            'locale'        => ['sometimes', 'string', 'in:en,ar'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'  => 'اسم المستخدم مستخدم بالفعل.',
            'username.regex'   => 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام وشرطة سفلية فقط.',
            'bio.max'          => 'النبذة الشخصية يجب أن لا تتجاوز 500 حرف.',
            'timezone.timezone' => 'المنطقة الزمنية غير صالحة.',
        ];
    }
}