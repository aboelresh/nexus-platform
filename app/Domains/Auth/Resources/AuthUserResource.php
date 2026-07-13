<?php

namespace App\Domains\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'username'        => $this->username,
            'email'           => $this->email,
            'avatar'          => $this->avatar_url,
            'bio'             => $this->bio,
            'custom_status'   => $this->custom_status,
            'presence_status' => $this->presence_status,
            'is_active'       => $this->is_active,
            'email_verified'  => !is_null($this->email_verified_at),
            'timezone'        => $this->timezone,
            'locale'          => $this->locale,
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}