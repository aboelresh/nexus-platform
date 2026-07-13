<?php

namespace App\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'min:2', 'max:100'],
            'username'    => ['required', 'string', 'min:3', 'max:30', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'timezone'    => ['nullable', 'string', 'timezone'],
            'locale'      => ['nullable', 'string', 'in:en,ar'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'الاسم مطلوب.',
            'name.min'             => 'الاسم يجب أن يكون على الأقل حرفين.',
            'username.required'    => 'اسم المستخدم مطلوب.',
            'username.unique'      => 'اسم المستخدم مستخدم بالفعل.',
            'username.regex'       => 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام وشرطة سفلية فقط.',
            'email.required'       => 'البريد الإلكتروني مطلوب.',
            'email.unique'         => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.required'    => 'كلمة المرور مطلوبة.',
            'password.min'         => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed'   => 'تأكيد كلمة المرور غير متطابق.',
        ];
    }
}