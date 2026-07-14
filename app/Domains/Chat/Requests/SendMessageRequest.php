<?php

namespace App\Domains\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'              => ['required_without:media_ids', 'nullable', 'string', 'max:10000'],
            'type'              => ['sometimes', 'string', 'in:text,image,video,audio,document,voice'],
            'reply_to_id'       => ['sometimes', 'nullable', 'integer', 'exists:messages,id'],
            'forwarded_from_id' => ['sometimes', 'nullable', 'integer', 'exists:messages,id'],
            'media_ids'         => ['required_without:body', 'nullable', 'array'],
            'media_ids.*'       => ['integer', 'exists:media,id'],
            'mentions'          => ['sometimes', 'array'],
            'mentions.*'        => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without'      => 'يجب إرسال نص أو ملف على الأقل.',
            'body.max'                   => 'الرسالة يجب أن لا تتجاوز 10000 حرف.',
            'reply_to_id.exists'         => 'الرسالة المراد الرد عليها غير موجودة.',
            'forwarded_from_id.exists'   => 'الرسالة المراد إعادة توجيهها غير موجودة.',
        ];
    }
}