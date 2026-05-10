<?php

namespace App\Http\Controllers;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Services\Bikube\AiAssistantService;
use Illuminate\Http\Request;

class AssistantChatController extends Controller
{
    public function startConversation(Request $r)
    {
        $conv = AssistantConversation::create([
            'title' => $r->input('title') ?? 'Courier chat',
            'channel' => 'courier',
            'created_by' => $r->user()?->id,
        ]);
        // initial system message
        AssistantMessage::create([
            'assistant_conversation_id' => $conv->id,
            'role' => 'system',
            'content' => config('ai_assistant.system_prompt'),
        ]);

        return response()->json($conv->load('messages'));
    }

    public function sendMessage(Request $r, AssistantConversation $conversation, AiAssistantService $ai)
    {
        $user = $r->user();
        
        // CRITICAL-3: Check ownership to prevent IDOR
        if ($conversation->created_by !== $user->id) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }
        
        $text = $r->validate(['message' => 'required|string'])['message'];
        $msg = AssistantMessage::create([
            'assistant_conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $text,
        ]);

        // push event to websocket so courier sees own message immediately
        broadcast(new \App\Events\AssistantMessageCreated($msg))->toOthers();

        // enqueue AI generation job
        \App\Jobs\GenerateAssistantReply::dispatch($conversation);

        return response()->json($msg);
    }

    public function messages(Request $r, AssistantConversation $conversation)
    {
        // CRITICAL-3: Check ownership to prevent IDOR
        if ($conversation->created_by !== $r->user()->id) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }
        
        return response()->json($conversation->messages()->orderBy('id')->get());
    }
}
