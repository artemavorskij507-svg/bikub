<?php

namespace App\Filament\Pages;

use App\Modules\AgencyAgents\Models\Agent;
use App\Modules\AgencyAgents\Models\AgentActivity;
use App\Modules\AgencyAgents\Models\AgentCommunication;
use App\Modules\AgencyAgents\Models\AgentTask;
use App\Modules\AgencyAgents\Models\OfficeZone;
use App\Modules\AgencyAgents\Services\AgentInitializationService;
use App\Modules\AgencyAgents\Services\AgentMonitoringService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class Virtual2DOffice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-grid';
    protected static ?string $navigationLabel = 'Virtual 2D Office';
    protected static ?string $title = 'Virtual 2D Office - Pixel Agents';
    protected static ?string $slug = 'virtual-2d-office';
    protected static ?int $navigationSort = 100;
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.virtual-2d-office';

    public array $agents=[],$zones=[],$recentActivities=[],$systemOverview=[],$zoneStats=[],$teamBoards=[],$taskBoard=[],$liveChat=[],$meetingTimeline=[],$liveNotifications=[],$directorConsole=[],$directorBacklog=[];
    public ?array $selectedAgent=null,$selectedZone=null;
    public string $filterCategory='all',$filterStatus='all',$filterZone='all',$directorTaskTitle='',$directorTaskDescription='',$directorTaskTargetUrl='http://127.0.0.1:2244/',$directorMessage='';
    public bool $showHeatmap=false,$showMinimap=true,$moduleReady=false,$directorOnlyMode=true;
    public int $officePopulation=0,$simulationSpeed=1;

    public function mount(): void {
        $this->directorOnlyMode=(bool)Cache::get('agency_office_director_only_mode',true);
        $this->simulationSpeed=(int)Cache::get('agency_office_simulation_speed',1);
        $this->ensureAgencySchema();
        $this->moduleReady=$this->hasAgencySchema();
        if($this->moduleReady){$this->bootstrapEcosystem();}
        $this->loadData();
    }

    public function loadData(): void {
        if(!$this->moduleReady){
            $this->agents=[];$this->zones=[];$this->recentActivities=[];$this->zoneStats=[];$this->teamBoards=[];$this->taskBoard=[];$this->liveChat=[];$this->meetingTimeline=[];$this->liveNotifications=[];
            $this->systemOverview=['status'=>'not_initialized','message'=>'Agency Agents tables are not migrated in the current database.'];
            return;
        }
        for($i=0;$i<max(1,$this->simulationSpeed);$i++){$this->simulateOfficeTick();}
        $this->simulateTesterValidation();
        $this->loadAgents();$this->loadZones();$this->loadSystemOverview();$this->loadRecentActivities();$this->loadZoneStats();$this->loadTeamBoards();$this->loadTaskBoard();$this->loadLiveChat();$this->loadMeetingTimeline();$this->loadLiveNotifications();$this->loadDirectorWorkspace();$this->sanitizeLivewireState();
    }

    public function refreshData(): void { $this->loadData(); }
    public function toggleDirectorOnlyMode(): void { $this->directorOnlyMode=!$this->directorOnlyMode; Cache::put('agency_office_director_only_mode',$this->directorOnlyMode,now()->addDays(7)); $this->loadData(); }
    public function setSimulationSpeed(int $speed): void { if(!in_array($speed,[1,2,4],true)) return; $this->simulationSpeed=$speed; Cache::put('agency_office_simulation_speed',$speed,now()->addDays(7)); $this->loadData(); }

    public function loadAgents(): void {
        if(!Schema::hasTable('agency_agents')){$this->agents=[];return;}
        $q=Agent::query();
        if($this->filterCategory!=='all')$q->where('category',$this->filterCategory);
        if($this->filterStatus!=='all')$q->where('status',$this->filterStatus);
        if($this->filterZone!=='all')$q->where('current_zone',$this->filterZone);
        $this->agents=$q->orderBy('name')->limit((int)config('agency-agents.office_2d.max_agents_display',120))->get()->map(function(Agent $a):array{
            $m=is_array($a->metadata)?$a->metadata:[];
            return ['id'=>$a->id,'name'=>$a->name,'emoji'=>$a->emoji,'category'=>$a->category,'status'=>$a->status,'color'=>$a->color,'performance_score'=>(float)$a->performance_score,'tasks_completed'=>(int)$a->tasks_completed,'current_zone'=>$a->current_zone,'position'=>['x'=>(float)$a->position_x,'y'=>(float)$a->position_y],'is_moving'=>(bool)$a->is_moving,'avatar_direction'=>$a->avatar_direction,'current_activity'=>$a->current_activity,'status_message'=>$a->status_message,'pixel_avatar'=>$m['pixel_style']??($m['pixel_avatar']??'worker'),'team_key'=>$m['team_key']??'general','team_name'=>$m['team_name']??'General Team','behavior'=>$m['behavior_pattern']??'balanced','last_active'=>$a->last_active_at?->diffForHumans()];
        })->toArray();
        $this->officePopulation=count($this->agents);
    }

    public function loadZones(): void {
        if(!Schema::hasTable('agency_office_zones')){$this->zones=[];return;}
        $this->zones=OfficeZone::query()->orderBy('name')->get()->map(fn(OfficeZone $z)=>['name'=>$z->name,'display_name'=>$z->display_name,'icon'=>$z->icon,'color'=>$z->color,'bounds'=>$z->bounds,'capacity'=>(int)$z->capacity,'current_occupancy'=>(int)$z->current_occupancy,'occupancy_percentage'=>round($z->getOccupancyPercentage(),1),'amenities'=>is_array($z->amenities)?$z->amenities:[]])->toArray();
    }

    public function loadSystemOverview(): void {
        $this->systemOverview=app(AgentMonitoringService::class)->getSystemOverview();
        $this->systemOverview['communications']=$this->systemOverview['communications']??['total_messages'=>0];
        $this->systemOverview['ecosystem']=['population'=>$this->officePopulation,'target_population'=>(int)config('agency-agents.office_2d.target_population',110),'active_teams'=>count($this->teamBoards),'live_channels'=>count(array_unique(array_column($this->liveChat,'channel')))];
    }

    public function loadRecentActivities(): void {
        if(!Schema::hasTable('agency_agent_activities')||!Schema::hasTable('agency_agents')){$this->recentActivities=[];return;}
        $this->recentActivities=AgentActivity::with('agent')->latest()->limit(30)->get()->map(fn(AgentActivity $a)=>['id'=>$a->id,'agent_name'=>$a->agent?->name??'Unknown','agent_emoji'=>$a->agent?->emoji??'bot','activity_type'=>$a->activity_type,'zone'=>$a->zone,'description'=>$a->description,'started_at'=>$a->started_at?->diffForHumans()])->toArray();
    }

    public function loadZoneStats(): void {
        if(!Schema::hasTable('agency_office_zones')){$this->zoneStats=[];return;}
        $this->zoneStats=OfficeZone::all()->map(fn(OfficeZone $z)=>['name'=>$z->name,'display_name'=>$z->display_name,'icon'=>$z->icon,'occupancy'=>(int)$z->current_occupancy,'capacity'=>(int)$z->capacity,'percentage'=>round($z->getOccupancyPercentage(),1)])->toArray();
    }

    public function loadTeamBoards(): void {
        if(!Schema::hasTable('agency_agents')){$this->teamBoards=[];return;}
        $groups=Agent::query()->get()->groupBy(function(Agent $a):string{$m=is_array($a->metadata)?$a->metadata:[]; return (string)($m['team_key']??$a->category??'general');});
        $this->teamBoards=$groups->map(function($agents,string $k):array{$f=$agents->first();$m=is_array($f?->metadata)?$f->metadata:[];return ['team_key'=>$k,'team_name'=>(string)($m['team_name']??ucfirst(str_replace(['-','_'],' ',$k))),'headcount'=>$agents->count(),'active'=>$agents->where('status','active')->count(),'busy'=>$agents->where('status','busy')->count(),'offline'=>$agents->where('status','offline')->count(),'lead'=>$f?->name,'zones'=>$agents->pluck('current_zone')->unique()->values()->toArray()];})->values()->toArray();
    }

    public function loadTaskBoard(): void {
        if(!Schema::hasTable('agency_agent_tasks')){$this->taskBoard=[];return;}
        $tasks=AgentTask::with('agent')->latest()->limit(80)->get();
        $this->taskBoard=['pending'=>$this->formatTasks($tasks->where('status','pending')),'in_progress'=>$this->formatTasks($tasks->where('status','in_progress')),'completed'=>$this->formatTasks($tasks->where('status','completed')),'failed'=>$this->formatTasks($tasks->where('status','failed'))];
    }

    public function loadLiveChat(): void {
        if(!Schema::hasTable('agency_agent_communications')||!Schema::hasTable('agency_agents')){$this->liveChat=[];return;}
        $q=AgentCommunication::with(['sender','receiver']);
        if($this->directorOnlyMode){$d=Agent::where('slug','director-agent')->first(); if($d){$q->where(function($x)use($d){$x->where('sender_agent_id',$d->id)->orWhere('receiver_agent_id',$d->id)->orWhere('message_type','user_instruction');});}}
        $this->liveChat=$q->latest()->limit($this->directorOnlyMode?40:80)->get()->map(function(AgentCommunication $c):array{$m=is_array($c->metadata)?$c->metadata:[];return ['id'=>$c->id,'type'=>$c->message_type,'priority'=>$c->priority,'from'=>$m['from_user']??($c->sender?->name??'System'),'to'=>$c->receiver?->name??'Team','content'=>$c->content,'channel'=>(string)($m['channel']??'office-general'),'asset'=>$m['asset_name']??null,'created_at'=>$c->created_at?->diffForHumans()];})->toArray();
    }
    public function loadMeetingTimeline(): void {
        if(!Schema::hasTable('agency_agent_activities')){$this->meetingTimeline=[];return;}
        $this->meetingTimeline=AgentActivity::with('agent')->whereIn('activity_type',['meeting','standup','code_review','brainstorm'])->latest()->limit(20)->get()->map(fn(AgentActivity $a)=>['id'=>$a->id,'event'=>$a->activity_type,'host'=>$a->agent?->name??'Unknown','zone'=>$a->zone,'description'=>$a->description,'time'=>$a->started_at?->format('H:i:s')])->toArray();
    }

    public function loadLiveNotifications(): void {
        $n=[];
        if(Schema::hasTable('agency_agent_tasks')){$n=array_merge($n,AgentTask::with('agent')->whereIn('status',['completed','failed'])->latest()->limit(10)->get()->map(fn(AgentTask $t)=>['id'=>'task-'.$t->id,'type'=>$t->status==='completed'?'task_completed':'task_failed','title'=>$t->title,'actor'=>$t->agent?->name??'Unknown','text'=>$t->status==='completed'?'Task completed and delivered to the sprint board.':'Task failed and requires reassignment.','time'=>$t->updated_at?->diffForHumans()])->toArray());}
        if(Schema::hasTable('agency_agent_communications')){$n=array_merge($n,AgentCommunication::with(['sender','receiver'])->whereIn('message_type',['asset_transfer','meeting_request','task_assignment','test_report','user_instruction'])->latest()->limit(12)->get()->map(function(AgentCommunication $c):array{$m=is_array($c->metadata)?$c->metadata:[];return ['id'=>'comm-'.$c->id,'type'=>$c->message_type,'title'=>ucfirst(str_replace('_',' ',$c->message_type)),'actor'=>$m['from_user']??($c->sender?->name??'Unknown'),'text'=>$m['asset_name']??$c->content,'time'=>$c->created_at?->diffForHumans()];})->toArray());}
        usort($n,fn($l,$r)=>strcmp((string)($r['time']??''),(string)($l['time']??'')));
        $this->liveNotifications=array_slice($n,0,18);
    }

    public function selectAgent(int $id): void {
        if(!$this->moduleReady||!Schema::hasTable('agency_agents')){$this->selectedAgent=null; $this->selectedZone = $this->sanitizeValue($this->selectedZone);return;}
        $a=Agent::query()->with(['tasks','sentCommunications','receivedCommunications'])->find($id);
        if(!$a){$this->selectedAgent=null; $this->selectedZone = $this->sanitizeValue($this->selectedZone);return;}
        $m=is_array($a->metadata)?$a->metadata:[];
        $this->selectedAgent=['id'=>$a->id,'name'=>$a->name,'category'=>$a->category,'status'=>$a->status,'zone'=>$a->current_zone,'x'=>$a->position_x,'y'=>$a->position_y,'performance'=>$a->performance_score,'tasks_completed'=>$a->tasks_completed,'activity'=>$a->current_activity,'message'=>$a->status_message,'team'=>$m['team_name']??'General Team','behavior'=>$m['behavior_pattern']??'balanced','task_count'=>$a->tasks->count(),'message_count'=>$a->sentCommunications->count()+$a->receivedCommunications->count(),'assets_exchanged'=>(int)($m['assets_exchanged']??0),'role_title'=>(string)($m['role_title']??ucfirst(str_replace(['-','_'],' ',(string)$a->category))),'prompt_trace'=>(string)($m['prompt_trace']??'Waiting for director instructions'),'inventory'=>is_array($m['inventory']??null)?$m['inventory']:['Terminal','Search','Editor'],'short_memory'=>is_array($m['short_memory']??null)?$m['short_memory']:['No active short-term context'],'confidence'=>(int)($m['confidence']??random_int(72,96))];
        $this->selectedZone=null; $this->selectedAgent = $this->sanitizeValue($this->selectedAgent);
    }

    public function selectZone(string $zoneName): void {
        if(!$this->moduleReady||!Schema::hasTable('agency_office_zones')){$this->selectedZone=null; $this->selectedAgent = $this->sanitizeValue($this->selectedAgent);return;}
        $z=OfficeZone::where('name',$zoneName)->first();
        if(!$z){$this->selectedZone=null; $this->selectedAgent = $this->sanitizeValue($this->selectedAgent);return;}
        $this->selectedZone=['name'=>$z->name,'display_name'=>$z->display_name,'capacity'=>$z->capacity,'occupancy'=>$z->current_occupancy,'percentage'=>round($z->getOccupancyPercentage(),1),'amenities'=>is_array($z->amenities)?$z->amenities:[]];
        $this->selectedAgent=null; $this->selectedZone = $this->sanitizeValue($this->selectedZone);
    }

    public function filterByCategory(string $v): void { $this->filterCategory=$v; $this->loadData(); }
    public function filterByStatus(string $v): void { $this->filterStatus=$v; $this->loadData(); }
    public function filterByZone(string $v): void { $this->filterZone=$v; $this->loadData(); }
    public function toggleHeatmap(): void { $this->showHeatmap=!$this->showHeatmap; }
    public function toggleMinimap(): void { $this->showMinimap=!$this->showMinimap; }

    protected function bootstrapEcosystem(): void {
        if(!Schema::hasTable('agency_agents')) return;
        app(AgentInitializationService::class)->ensureMinimumHeadcount(max(100,(int)config('agency-agents.office_2d.target_population',110)),(string)config('agency-agents.office_2d.shared_api_key',''));
        $this->syncZoneOccupancy();
    }

    protected function simulateOfficeTick(): void {
        if(!Schema::hasTable('agency_agents')||!Schema::hasTable('agency_office_zones')) return;
        if($this->directorOnlyMode){$this->stabilizeAgentsForDirectorMode(); return;}
        $k='agency_office_tick_lock'; if(Cache::has($k)) return; Cache::put($k,true,now()->addSeconds(8));
        $zones=OfficeZone::all(); if($zones->isEmpty()) return;
        Agent::query()->inRandomOrder()->limit(14)->get()->each(function(Agent $a)use($zones){$z=$zones->random();$p=$z->getRandomPosition();$m=is_array($a->metadata)?$a->metadata:[];$a->update(['status'=>collect(['active','busy','idle'])->random(),'current_activity'=>collect(['coding','meeting','review','analysis','break'])->random(),'status_message'=>'Working in '.$z->display_name,'current_zone'=>$z->name,'position_x'=>$p['x'],'position_y'=>$p['y'],'is_moving'=>(bool)random_int(0,1),'avatar_direction'=>collect(['up','down','left','right'])->random(),'last_active_at'=>now(),'metadata'=>array_merge($m,['last_cycle_at'=>now()->toISOString()])]);});
        $this->syncZoneOccupancy();
    }

    protected function formatTasks($tasks): array { return $tasks->take(20)->map(fn(AgentTask $t)=>['id'=>$t->id,'title'=>$t->title,'agent'=>$t->agent?->name??'Unknown','priority'=>$t->priority,'progress'=>(int)$t->progress,'updated_at'=>$t->updated_at?->diffForHumans()])->values()->toArray(); }

    public function submitDirectorTask(): void {
        if(!$this->moduleReady||!Schema::hasTable('agency_agents')||!Schema::hasTable('agency_agent_tasks')) return;
        $title=trim($this->directorTaskTitle); $desc=trim($this->directorTaskDescription); if($title===''||$desc==='') return;
        $d=$this->ensureDirectorAgent(); if(!$d) return;
        $iter=(int)AgentTask::where('agent_id',$d->id)->where('title','like','Design Iteration:%')->count()+1;
        $task=AgentTask::create(['agent_id'=>$d->id,'title'=>'Design Iteration: '.$title,'description'=>$desc,'status'=>'in_progress','priority'=>'high','category'=>'design','progress'=>5,'metadata'=>['target_url'=>$this->directorTaskTargetUrl,'iteration'=>$iter,'assigned_by_user_id'=>auth()->id(),'requirements'=>['functionality','appearance','ux_consistency','responsive_layout','cross_browser']]]);
        $this->dispatchDirectorChain($d,$task,$iter,$this->directorTaskTargetUrl);
        $this->directorTaskTitle='';$this->directorTaskDescription='';$this->loadData();
    }

    public function sendMessageToDirector(): void {
        if(!$this->moduleReady||!Schema::hasTable('agency_agent_communications')) return;
        $msg=trim($this->directorMessage); if($msg==='') return;
        $d=$this->ensureDirectorAgent(); if(!$d) return;
        AgentCommunication::create(['sender_agent_id'=>$d->id,'receiver_agent_id'=>$d->id,'message_type'=>'user_instruction','content'=>$msg,'status'=>'sent','priority'=>'normal','metadata'=>['channel'=>'director-console','user_id'=>auth()->id(),'from_user'=>auth()->user()?->name??'Admin']]);
        if(Schema::hasTable('agency_agent_tasks')){$iter=(int)AgentTask::where('agent_id',$d->id)->where('title','like','Design Iteration:%')->count()+1;$task=AgentTask::create(['agent_id'=>$d->id,'title'=>'Design Iteration: '.str($msg)->limit(70,'...'),'description'=>$msg,'status'=>'in_progress','priority'=>'high','category'=>'design','progress'=>10,'metadata'=>['target_url'=>$this->directorTaskTargetUrl,'iteration'=>$iter,'assigned_by_user_id'=>auth()->id(),'source'=>'director_console_chat','requirements'=>['functionality','appearance','ux_consistency','responsive_layout','cross_browser']]]);$this->dispatchDirectorChain($d,$task,$iter,$this->directorTaskTargetUrl);}
        $this->directorMessage='';$this->loadDirectorWorkspace();$this->sanitizeLivewireState();
    }

    protected function loadDirectorWorkspace(): void {
        if(!$this->moduleReady){$this->directorConsole=[];$this->directorBacklog=[];return;}
        $d=$this->ensureDirectorAgent(); if(!$d){$this->directorConsole=[];$this->directorBacklog=[];return;}
        $this->directorBacklog=AgentTask::with('agent')->where('agent_id',$d->id)->latest()->limit(20)->get()->map(function(AgentTask $t):array{$m=is_array($t->metadata)?$t->metadata:[];return ['id'=>$t->id,'title'=>$t->title,'status'=>$t->status,'priority'=>$t->priority,'iteration'=>$m['iteration']??null,'target_url'=>$m['target_url']??null,'updated_at'=>$t->updated_at?->diffForHumans()];})->toArray();
        $this->directorConsole=AgentCommunication::with(['sender','receiver'])->where(function($q)use($d){$q->where('sender_agent_id',$d->id)->orWhere('receiver_agent_id',$d->id);})->latest()->limit(25)->get()->map(function(AgentCommunication $c):array{$m=is_array($c->metadata)?$c->metadata:[];return ['id'=>$c->id,'type'=>$c->message_type,'from'=>$m['from_user']??($c->sender?->name??'System'),'to'=>$c->receiver?->name??'Director','message'=>$c->content,'channel'=>$m['channel']??'director-console','created_at'=>$c->created_at?->diffForHumans()];})->toArray();
    }

    protected function simulateTesterValidation(): void {
        if(!Schema::hasTable('agency_agent_tasks')||!Schema::hasTable('agency_agent_communications')) return;
        $tests=AgentTask::with('agent')->where('category','qa')->whereIn('status',['pending','in_progress'])->latest()->limit(6)->get();
        foreach($tests as $t){if(random_int(1,100)>($this->directorOnlyMode?62:38)) continue; $chk=['functionality'=>collect(['ok','issue'])->random(),'appearance'=>collect(['ok','issue'])->random(),'ux'=>collect(['ok','issue'])->random(),'responsive'=>collect(['ok','issue'])->random(),'cross_browser'=>collect(['ok','issue'])->random()]; $issues=collect($chk)->filter(fn($v)=>$v==='issue')->keys()->values()->toArray(); $status=empty($issues)?'completed':'in_progress'; $t->update(['status'=>$status,'progress'=>$status==='completed'?100:random_int(40,85),'result'=>empty($issues)?'Passed checklist':'Needs fixes in: '.implode(', ',$issues),'metadata'=>array_merge(is_array($t->metadata)?$t->metadata:[],['checklist_result'=>$chk,'last_reported_at'=>now()->toISOString(),'browser_matrix'=>['Chrome','Firefox','Edge','Safari']])]); $parent=$t->metadata['parent_director_task_id']??null; if(!$parent) continue; $dt=AgentTask::find($parent); $d=$dt?Agent::find($dt->agent_id):null; if($d&&$t->agent){AgentCommunication::create(['sender_agent_id'=>$t->agent_id,'receiver_agent_id'=>$d->id,'message_type'=>'test_report','content'=>empty($issues)?'QA report: iteration passed on all checklist points.':'QA report: found issues in '.implode(', ',$issues),'status'=>'sent','priority'=>empty($issues)?'normal':'high','metadata'=>['channel'=>'director-qa','checklist'=>$chk,'source_task_id'=>$t->id,'parent_task_id'=>$parent]]);}}
    }

    protected function ensureDirectorAgent(): ?Agent {
        if(!Schema::hasTable('agency_agents')) return null;
        $d=Agent::where('slug','director-agent')->first();
        return $d?:Agent::create(['name'=>'Director Agent','slug'=>'director-agent','description'=>'Coordinates design iterations and tester workflow.','category'=>'project-management','color'=>'indigo','emoji'=>'director','status'=>'active','current_zone'=>'meeting_room','position_x'=>620,'position_y'=>80,'avatar_direction'=>'down','is_moving'=>false,'current_activity'=>'coordination','status_message'=>'Awaiting tasks from admin','metadata'=>['team_key'=>'director-office','team_name'=>'Director Office','behavior_pattern'=>'orchestrator','pixel_style'=>'openclaw-director']]);
    }

    protected function resolveTeamKey(?string $c): string { return match($c){'engineering','game-development'=>'builders','design','product'=>'design-product','project-management','strategy'=>'operations','marketing','sales','paid-media'=>'growth',default=>'specialists'}; }
    protected function resolveTeamChannel(?string $c): string { return 'office-'.$this->resolveTeamKey($c); }

    protected function stabilizeAgentsForDirectorMode(): void {
        $k='agency_office_director_stabilize_lock'; if(Cache::has($k)) return; Cache::put($k,true,now()->addSeconds(20));
        Agent::query()->where('slug','!=','director-agent')->whereIn('status',['active','busy'])->limit(40)->get()->each(function(Agent $a):void{$m=is_array($a->metadata)?$a->metadata:[];$a->update(['status'=>'idle','current_activity'=>'standby','status_message'=>'Awaiting director instructions','is_moving'=>false,'metadata'=>array_merge($m,['behavior_pattern'=>'listener'])]);});
    }

    protected function dispatchDirectorChain(Agent $d, AgentTask $task, int $iter, ?string $url): void {
        $pool=Agent::whereIn('category',['engineering','specialized','design'])->where('id','!=',$d->id)->inRandomOrder()->limit(6)->get();
        foreach($pool as $t){AgentTask::create(['agent_id'=>$t->id,'title'=>'QA Checklist for iteration #'.$iter,'description'=>'Validate: '.$task->title,'status'=>'pending','priority'=>'high','category'=>'qa','progress'=>0,'metadata'=>['parent_director_task_id'=>$task->id,'target_url'=>$url,'test_type'=>collect(['manual','automated'])->random(),'checklist'=>['layout matches requirements','primary actions are clickable','forms validate correctly','mobile viewport is usable','no major visual regressions']]]); if(Schema::hasTable('agency_agent_communications')){AgentCommunication::create(['sender_agent_id'=>$d->id,'receiver_agent_id'=>$t->id,'message_type'=>'task_assignment','content'=>'Run QA checklist for iteration #'.$iter.' on '.($url?:'current target'),'status'=>'sent','priority'=>'high','metadata'=>['channel'=>'director-qa','iteration'=>$iter,'parent_task_id'=>$task->id]]);}}
    }

    protected function syncZoneOccupancy(): void { if(!Schema::hasTable('agency_office_zones')||!Schema::hasTable('agency_agents')) return; foreach(OfficeZone::all() as $z){$z->updateOccupancy();} }

    protected function sanitizeLivewireState(): void {
        $this->agents = $this->sanitizeValue($this->agents);
        $this->zones = $this->sanitizeValue($this->zones);
        $this->recentActivities = $this->sanitizeValue($this->recentActivities);
        $this->systemOverview = $this->sanitizeValue($this->systemOverview);
        $this->zoneStats = $this->sanitizeValue($this->zoneStats);
        $this->teamBoards = $this->sanitizeValue($this->teamBoards);
        $this->taskBoard = $this->sanitizeValue($this->taskBoard);
        $this->liveChat = $this->sanitizeValue($this->liveChat);
        $this->meetingTimeline = $this->sanitizeValue($this->meetingTimeline);
        $this->liveNotifications = $this->sanitizeValue($this->liveNotifications);
        $this->directorConsole = $this->sanitizeValue($this->directorConsole);
        $this->directorBacklog = $this->sanitizeValue($this->directorBacklog);
        $this->selectedAgent = $this->sanitizeValue($this->selectedAgent);
        $this->selectedZone = $this->sanitizeValue($this->selectedZone);
        $this->directorTaskTitle = (string)$this->sanitizeValue($this->directorTaskTitle);
        $this->directorTaskDescription = (string)$this->sanitizeValue($this->directorTaskDescription);
        $this->directorTaskTargetUrl = (string)$this->sanitizeValue($this->directorTaskTargetUrl);
        $this->directorMessage = (string)$this->sanitizeValue($this->directorMessage);
    }

    protected function sanitizeValue(mixed $value): mixed {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->sanitizeValue($v);
            }
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            $value = $converted !== false ? $converted : '';
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    }

    protected function getViewData(): array { return ['agents'=>$this->agents,'zones'=>$this->zones,'selectedAgent'=>$this->selectedAgent,'selectedZone'=>$this->selectedZone,'filterCategory'=>$this->filterCategory,'filterStatus'=>$this->filterStatus,'filterZone'=>$this->filterZone,'showHeatmap'=>$this->showHeatmap,'showMinimap'=>$this->showMinimap,'recentActivities'=>$this->recentActivities,'systemOverview'=>$this->systemOverview,'zoneStats'=>$this->zoneStats,'categories'=>config('agency-agents.categories',[]),'moduleReady'=>$this->moduleReady,'teamBoards'=>$this->teamBoards,'taskBoard'=>$this->taskBoard,'liveChat'=>$this->liveChat,'meetingTimeline'=>$this->meetingTimeline,'liveNotifications'=>$this->liveNotifications,'officePopulation'=>$this->officePopulation,'directorConsole'=>$this->directorConsole,'directorBacklog'=>$this->directorBacklog,'directorTaskTitle'=>$this->directorTaskTitle,'directorTaskDescription'=>$this->directorTaskDescription,'directorTaskTargetUrl'=>$this->directorTaskTargetUrl,'directorMessage'=>$this->directorMessage,'directorOnlyMode'=>$this->directorOnlyMode,'simulationSpeed'=>$this->simulationSpeed]; }

    protected function ensureAgencySchema(): void {
        if($this->hasAgencySchema()) return;
        if(!app()->environment('local')||config('database.default')!=='sqlite') return;
        try {
            Artisan::call('migrate',['--path'=>'database/migrations/2024_01_01_000001_create_agency_agents_tables.php','--force'=>true]);
            Artisan::call('migrate',['--path'=>'database/migrations/2026_03_31_150000_create_agency_agent_module_assignments_and_event_logs.php','--force'=>true]);
        } catch (\Throwable $e) {
            Log::warning('Failed to auto-migrate AgencyAgents schema for Virtual2DOffice',['error'=>$e->getMessage()]);
        }
    }

    protected function hasAgencySchema(): bool {
        return Schema::hasTable('agency_agents') && Schema::hasTable('agency_agent_tasks') && Schema::hasTable('agency_agent_communications') && Schema::hasTable('agency_office_zones');
    }
}