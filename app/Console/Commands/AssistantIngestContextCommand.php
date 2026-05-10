<?php

namespace App\Console\Commands;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use Illuminate\Console\Command;

class AssistantIngestContextCommand extends Command
{
    protected $signature = 'assistant:ingest-context {path}';

    protected $description = 'Ingest local context file into assistant knowledge as system messages';

    public function handle()
    {
        $path = $this->argument('path');

        // Remove file:// prefix if present
        $path = str_replace('file://', '', $path);

        if (! file_exists($path)) {
            $this->error('File not found: '.$path);

            return 1;
        }

        $text = file_get_contents($path);
        $conv = AssistantConversation::create([
            'title' => 'Project context import',
            'channel' => 'admin',
        ]);

        AssistantMessage::create([
            'assistant_conversation_id' => $conv->id,
            'role' => 'system',
            'content' => substr($text, 0, 30000),
        ]);

        $this->info('Context ingested into conversation id '.$conv->id);

        return 0;
    }
}
