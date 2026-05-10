<?php

namespace App\Jobs;

use App\Models\AssistantConversation;
use App\Services\Bikube\AiAssistantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAssistantReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $conversationId;

    public function __construct(AssistantConversation $conv)
    {
        $this->conversationId = $conv->id;
    }

    public function handle(AiAssistantService $ai)
    {
        $conv = AssistantConversation::find($this->conversationId);
        if (! $conv) {
            return;
        }
        $msg = $ai->generateReply($conv);
        broadcast(new \App\Events\AssistantMessageCreated($msg));
    }
}
