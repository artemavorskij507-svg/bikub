<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantConversation extends Model
{
    protected $fillable = ['title', 'channel', 'created_by', 'subject_type', 'subject_id'];

    public function messages()
    {
        return $this->hasMany(AssistantMessage::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
