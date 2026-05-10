<?php

namespace App\Filament\Resources\AssistantConversationResource\Pages;

use App\Filament\Resources\AssistantConversationResource;
use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssistantConversations extends ListRecords
{
    protected static string $resource = AssistantConversationResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedLocalDemoConversationsIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function seedLocalDemoConversationsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (AssistantConversation::query()->exists()) {
            return;
        }

        $users = User::query()->orderBy('id')->limit(3)->get();

        if ($users->isEmpty()) {
            return;
        }

        $channels = ['courier', 'admin', 'support'];

        foreach ($users as $index => $user) {
            $conversation = AssistantConversation::query()->create([
                'title' => 'Demo conversation #'.($index + 1),
                'channel' => $channels[$index % count($channels)],
                'created_by' => $user->id,
                'subject_type' => User::class,
                'subject_id' => $user->id,
            ]);

            AssistantMessage::query()->create([
                'assistant_conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'user',
                'content' => 'Hello, I need help with my request.',
                'meta' => ['source' => 'local_demo_seed'],
                'from_ai' => false,
            ]);

            AssistantMessage::query()->create([
                'assistant_conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'role' => 'assistant',
                'content' => 'Sure, I can help. Please share order details.',
                'meta' => ['source' => 'local_demo_seed'],
                'from_ai' => true,
            ]);
        }
    }
}
