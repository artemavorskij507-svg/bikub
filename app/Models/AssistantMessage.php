<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    protected $fillable = ['assistant_conversation_id', 'user_id', 'role', 'content', 'meta', 'from_ai'];

    protected $casts = [
        'meta' => 'array',
        'from_ai' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(AssistantConversation::class);
    }
}
