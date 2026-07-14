<?php

namespace App\Domains\Chat\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class MessageMention extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}