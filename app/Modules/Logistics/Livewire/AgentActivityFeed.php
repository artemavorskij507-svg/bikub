<?php

namespace App\Modules\Logistics\Livewire;

use App\Modules\AgencyAgents\Models\AgentActivity;
use Livewire\Component;

class AgentActivityFeed extends Component
{
    public function render()
    {
        return view('livewire.logistics.agent-activity-feed', [
            'activities' => AgentActivity::query()->latest('created_at')->limit(30)->get(),
        ]);
    }
}
