<?php

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة.',
            'password.required'         => 'كلمة المرور الجديدة مطلوبة.',
            'password.min'              => 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed'        => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}