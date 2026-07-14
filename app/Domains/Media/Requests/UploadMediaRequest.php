<?php

namespace App\Domains\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'            => ['required', 'file', 'max:102400'],
            'type'            => ['required', 'string', 'in:image,video,audio,document,voice'],
            'conversation_id' => ['sometimes', 'integer', 'exists:conversations,id'],
            'message_id'      => ['sometimes', 'integer', 'exists:messages,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'يجب رفع ملف.',
            'file.max'      => 'حجم الملف يجب أن لا يتجاوز 100 ميجابايت.',
            'type.in'       => 'نوع الملف غير مدعوم.',
        ];
    }
}