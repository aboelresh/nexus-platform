<?php

namespace App\Domains\Group\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'min:2', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'type'        => ['sometimes', 'string', 'in:public,private,invite_only'],
            'max_members' => ['sometimes', 'integer', 'min:2', 'max:10000'],
            'settings'    => ['sometimes', 'array'],
            'settings.who_can_send_messages' => ['sometimes', 'in:everyone,members,admins'],
            'settings.who_can_add_members'   => ['sometimes', 'in:everyone,members,admins'],
            'settings.who_can_edit_info'     => ['sometimes', 'in:members,admins'],
            'settings.join_approval'         => ['sometimes', 'boolean'],
            'settings.show_member_list'      => ['sometimes', 'boolean'],
        ];
    }
}