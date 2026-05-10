<?php

namespace App\Console\Commands;

use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\Category;
use App\Models\VirtualOffice\OfficeZone;
use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Message;
use Illuminate\Console\Command;

class SeedVirtualOfficeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virtual-office:seed 
                            {--fresh : Truncate tables before seeding}
                            {--agents=20 : Number of agents to create}
                            {--tasks=50 : Number of tasks to create}
                            {--messages=30 : Number of messages to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed virtual office with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fresh = $this->option('fresh');
        $agentsCount = $this->option('agents');
        $tasksCount = $this->option('tasks');
        $messagesCount = $this->option('messages');

        if ($fresh) {
            $this->info('Truncating tables...');
            Message::truncate();
            Task::truncate();
            Agent::truncate();
            OfficeZone::truncate();
            Category::truncate();
        }

        $this->info('Seeding virtual office...');

        // Create categories
        $this->info('Creating categories...');
        $categories = $this->createCategories();
        $this->info('  ✓ Created ' . count($categories) . ' categories');

        // Create zones
        $this->info('Creating zones...');
        $zones = $this->createZones();
        $this->info('  ✓ Created ' . count($zones) . ' zones');

        // Create agents
        $this->info('Creating agents...');
        $agents = $this->createAgents($categories, $zones, $agentsCount);
        $this->info('  ✓ Created ' . count($agents) . ' agents');

        // Create tasks
        $this->info('Creating tasks...');
        $tasks = $this->createTasks($agents, $zones, $tasksCount);
        $this->info('  ✓ Created ' . count($tasks) . ' tasks');

        // Create messages
        $this->info('Creating messages...');
        $messages = $this->createMessages($agents, $zones, $messagesCount);
        $this->info('  ✓ Created ' . count($messages) . ' messages');

        $this->info('');
        $this->info('Virtual office seeded successfully!');
        $this->info('Categories: ' . Category::count());
        $this->info('Zones: ' . OfficeZone::count());
        $this->info('Agents: ' . Agent::count());
        $this->info('Tasks: ' . Task::count());
        $this->info('Messages: ' . Message::count());

        return 0;
    }

    /**
     * Create categories
     */
    protected function createCategories()
    {
        $categories = [
            ['name' => 'Engineering', 'slug' => 'engineering', 'color' => '#3B82F6', 'description' => 'Software engineering agents'],
            ['name' => 'Design', 'slug' => 'design', 'color' => '#8B5CF6', 'description' => 'Design and UX agents'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'color' => '#EC4899', 'description' => 'Marketing and growth agents'],
            ['name' => 'Sales', 'slug' => 'sales', 'color' => '#10B981', 'description' => 'Sales and business development agents'],
            ['name' => 'Project Management', 'slug' => 'project-management', 'color' => '#F59E0B', 'description' => 'Project management agents'],
            ['name' => 'Testing', 'slug' => 'testing', 'color' => '#EF4444', 'description' => 'Quality assurance and testing agents'],
            ['name' => 'Specialized', 'slug' => 'specialized', 'color' => '#6366F1', 'description' => 'Specialized domain agents'],
            ['name' => 'Game Development', 'slug' => 'game-development', 'color' => '#14B8A6', 'description' => 'Game development agents'],
        ];

        $created = [];
        foreach ($categories as $category) {
            $created[] = Category::create($category);
        }

        return $created;
    }

    /**
     * Create zones
     */
    protected function createZones()
    {
        $zones = [
            ['name' => 'Main Workspace', 'slug' => 'main-workspace', 'description' => 'Primary workspace for all agents', 'color' => '#3B82F6', 'x' => 50, 'y' => 50, 'width' => 300, 'height' => 200],
            ['name' => 'Meeting Room', 'slug' => 'meeting-room', 'description' => 'Collaboration and meetings', 'color' => '#8B5CF6', 'x' => 400, 'y' => 50, 'width' => 200, 'height' => 150],
            ['name' => 'Brainstorm Zone', 'slug' => 'brainstorm-zone', 'description' => 'Creative brainstorming area', 'color' => '#EC4899', 'x' => 50, 'y' => 300, 'width' => 250, 'height' => 180],
            ['name' => 'Break Room', 'slug' => 'break-room', 'description' => 'Relaxation and social area', 'color' => '#10B981', 'x' => 350, 'y' => 300, 'width' => 180, 'height' => 150],
            ['name' => 'Cafeteria', 'slug' => 'cafeteria', 'description' => 'Food and drinks area', 'color' => '#F59E0B', 'x' => 580, 'y' => 50, 'width' => 150, 'height' => 120],
            ['name' => 'Lounge', 'slug' => 'lounge', 'description' => 'Comfortable seating area', 'color' => '#EF4444', 'x' => 580, 'y' => 200, 'width' => 150, 'height' => 150],
        ];

        $created = [];
        foreach ($zones as $zone) {
            $created[] = OfficeZone::create($zone);
        }

        return $created;
    }

    /**
     * Create agents
     */
    protected function createAgents($categories, $zones, $count)
    {
        $agentNames = [
            'Alex Chen', 'Maria Garcia', 'James Wilson', 'Sarah Johnson', 'Michael Brown',
            'Emily Davis', 'David Miller', 'Jessica Martinez', 'Robert Anderson', 'Ashley Thomas',
            'Christopher Jackson', 'Amanda White', 'Matthew Harris', 'Stephanie Martin', 'Daniel Thompson',
            'Nicole Garcia', 'Andrew Martinez', 'Brittany Robinson', 'Joshua Clark', 'Lauren Lewis',
        ];

        $roles = [
            'Senior Developer', 'Frontend Engineer', 'Backend Architect', 'DevOps Specialist',
            'UX Designer', 'Product Manager', 'QA Engineer', 'Data Scientist',
            'Marketing Specialist', 'Sales Representative', 'Project Manager', 'Security Engineer',
        ];

        $statuses = ['active', 'busy', 'idle', 'offline'];

        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $category = $categories[array_rand($categories)];
            $zone = $zones[array_rand($zones)];
            
            $agent = Agent::create([
                'name' => $agentNames[$i % count($agentNames)],
                'slug' => 'agent-' . ($i + 1),
                'role' => $roles[array_rand($roles)],
                'description' => 'Experienced professional with expertise in ' . strtolower($category->name),
                'category_id' => $category->id,
                'current_zone_id' => $zone->id,
                'status' => $statuses[array_rand($statuses)],
                'skills' => ['Problem Solving', 'Communication', 'Teamwork', 'Leadership'],
                'position_x' => rand(50, 750),
                'position_y' => rand(50, 550),
                'metadata' => ['experience' => rand(1, 10) . ' years'],
            ]);

            $created[] = $agent;
        }

        return $created;
    }

    /**
     * Create tasks
     */
    protected function createTasks($agents, $zones, $count)
    {
        $taskTitles = [
            'Review code changes', 'Update documentation', 'Fix bug in login flow',
            'Implement new feature', 'Optimize database queries', 'Write unit tests',
            'Deploy to production', 'Monitor system performance', 'Conduct code review',
            'Update dependencies', 'Refactor legacy code', 'Create API endpoints',
            'Design user interface', 'Conduct user research', 'Analyze metrics',
            'Prepare presentation', 'Write technical spec', 'Coordinate with team',
            'Test new release', 'Investigate issue',
        ];

        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];

        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $agent = $agents[array_rand($agents)];
            $zone = $zones[array_rand($zones)];
            
            $task = Task::create([
                'title' => $taskTitles[$i % count($taskTitles)],
                'description' => 'Task description for ' . $taskTitles[$i % count($taskTitles)],
                'agent_id' => $agent->id,
                'zone_id' => $zone->id,
                'status' => $statuses[array_rand($statuses)],
                'priority' => $priorities[array_rand($priorities)],
                'due_date' => now()->addDays(rand(1, 30)),
                'completed_at' => rand(0, 1) ? now()->subDays(rand(1, 10)) : null,
            ]);

            $created[] = $task;
        }

        return $created;
    }

    /**
     * Create messages
     */
    protected function createMessages($agents, $zones, $count)
    {
        $messageContents = [
            'Hey, can you review my PR?', 'Meeting at 3pm today', 'Great work on the presentation!',
            'I need help with this bug', 'Let\'s discuss the new feature', 'Code review completed',
            'Deployment successful', 'Issue resolved', 'New task assigned', 'Status update needed',
            'Thanks for your help!', 'Can we sync up?', 'I have a question about the spec',
            'The tests are passing now', 'Ready for review', 'Need your input on this',
        ];

        $types = ['text', 'task', 'alert', 'notification'];

        $created = [];
        for ($i = 0; $i < $count; $i++) {
            $sender = $agents[array_rand($agents)];
            $receiver = $agents[array_rand($agents)];
            $zone = $zones[array_rand($zones)];
            
            $message = Message::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'zone_id' => $zone->id,
                'content' => $messageContents[$i % count($messageContents)],
                'type' => $types[array_rand($types)],
                'read_at' => rand(0, 1) ? now()->subHours(rand(1, 24)) : null,
            ]);

            $created[] = $message;
        }

        return $created;
    }
}
