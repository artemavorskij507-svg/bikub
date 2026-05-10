<?php

namespace App\Events;

use App\Models\AssistantMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class AssistantMessageCreated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(AssistantMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('assistant.conversation.'.$this->message->assistant_conversation_id);
    }

    public function broadcastWith()
    {
        return ['message' => $this->message->toArray()];
    }
}
