<?php

namespace App\Livewire\VirtualOffice;

use App\Models\VirtualOffice\Agent;
use App\Models\VirtualOffice\OfficeZone;
use App\Models\VirtualOffice\Task;
use App\Models\VirtualOffice\Message;
use Livewire\Component;
use Livewire\WithPagination;

class OfficeCanvas extends Component
{
    use WithPagination;

    public $selectedZone = null;
    public $selectedAgent = null;
    public $showAgentModal = false;
    public $showTaskModal = false;
    public $showMessageModal = false;
    public $agents = [];
    public $zones = [];
    public $tasks = [];
    public $messages = [];
    public $filterCategory = null;
    public $filterStatus = null;
    public $searchQuery = '';

    protected $listeners = [
        'refreshCanvas' => '$refresh',
        'selectZone' => 'selectZone',
        'selectAgent' => 'selectAgent',
        'createTask' => 'createTask',
        'sendMessage' => 'sendMessage',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $query = Agent::with(['category', 'zone', 'tasks']);

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterStatus) {
            $query->where('is_active', in_array($this->filterStatus, ['active', 'busy', 'idle'], true));
        }

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('slug', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
            });
        }

        $this->agents = $query->get();
        $this->zones = OfficeZone::withCount('agents')->get();
        $this->tasks = Task::with(['agent', 'zone'])->latest()->take(10)->get();
        $this->messages = Message::with(['sender', 'receiver', 'zone'])->latest()->take(10)->get();
    }

    public function selectZone($zoneId)
    {
        $this->selectedZone = OfficeZone::find($zoneId);
        $this->selectedAgent = null;
    }

    public function selectAgent($agentId)
    {
        $this->selectedAgent = Agent::with(['category', 'zone', 'tasks'])->find($agentId);
        $this->showAgentModal = true;
    }

    public function createTask($data)
    {
        Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'agent_id' => $data['agent_id'],
            'zone_id' => $data['zone_id'] ?? null,
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
        ]);

        $this->showTaskModal = false;
        $this->loadData();
        $this->dispatch('taskCreated');
    }

    public function sendMessage($data)
    {
        Message::create([
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'] ?? null,
            'zone_id' => $data['zone_id'] ?? null,
            'content' => $data['content'],
            'type' => $data['type'] ?? 'text',
        ]);

        $this->showMessageModal = false;
        $this->loadData();
        $this->dispatch('messageSent');
    }

    public function updateAgentPosition($agentId, $x, $y)
    {
        $agent = Agent::find($agentId);
        if ($agent) {
            $agent->update([
                'x_position' => $x,
                'y_position' => $y,
            ]);
        }
    }

    public function updateAgentStatus($agentId, $status)
    {
        $agent = Agent::find($agentId);
        if ($agent) {
            $agent->update([
                'is_active' => in_array($status, ['active', 'busy', 'idle'], true),
            ]);
            $this->loadData();
        }
    }

    public function updateTaskStatus($taskId, $status)
    {
        $task = Task::find($taskId);
        if ($task) {
            $task->update(['status' => $status]);
            $this->loadData();
        }
    }

    public function filterByCategory($categoryId)
    {
        $this->filterCategory = $categoryId;
        $this->loadData();
    }

    public function filterByStatus($status)
    {
        $this->filterStatus = $status;
        $this->loadData();
    }

    public function clearFilters()
    {
        $this->filterCategory = null;
        $this->filterStatus = null;
        $this->searchQuery = '';
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.virtual-office.office-canvas');
    }
}
