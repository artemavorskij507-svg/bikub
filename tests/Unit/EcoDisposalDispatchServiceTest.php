<?php

namespace Tests\Unit;

use App\Models\DisposalPartner;
use App\Models\EcoTeam;
use App\Models\Order;
use App\Models\User;
use App\Services\EcoDisposal\EcoDisposalDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcoDisposalDispatchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EcoDisposalDispatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EcoDisposalDispatchService::class);
    }

    protected function createEcoOrder(): Order
    {
        return Order::factory()->create([
            'status' => 'pending',
            'metadata' => ['service_type' => 'eco_disposal'],
        ]);
    }

    /** @test */
    public function it_assigns_team_and_moves_to_assigned()
    {
        $order = $this->createEcoOrder();
        $team = EcoTeam::factory()->create(['is_active' => true]);
        $dispatcher = User::factory()->create();

        $updated = $this->service->assignTeamToOrder($order, $team, $dispatcher);
        $updated->refresh()->load('disposalDetails.ecoTeam');

        $this->assertEquals('ASSIGNED', strtoupper($updated->disposalDetails->eco_status));
        $this->assertEquals($team->id, $updated->disposalDetails->eco_team_id);
    }

    /** @test */
    public function it_goes_in_progress_only_from_assigned()
    {
        $order = $this->createEcoOrder();
        $team = EcoTeam::factory()->create(['is_active' => true]);
        $this->service->assignTeamToOrder($order, $team);

        $updated = $this->service->markInProgress($order);
        $this->assertEquals('IN_PROGRESS', strtoupper($updated->disposalDetails->eco_status));
    }

    /** @test */
    public function it_goes_at_partner_only_from_in_progress()
    {
        $order = $this->createEcoOrder();
        $team = EcoTeam::factory()->create(['is_active' => true]);
        $partner = DisposalPartner::factory()->create(['is_active' => true]);
        $this->service->assignTeamToOrder($order, $team);
        $this->service->markInProgress($order);

        $updated = $this->service->markAtPartner($order, $partner);
        $this->assertEquals('AT_PARTNER', strtoupper($updated->disposalDetails->eco_status));
        $this->assertEquals($partner->id, $updated->disposalDetails->eco_partner_id);
    }

    /** @test */
    public function it_completes_from_in_progress_or_at_partner()
    {
        $order = $this->createEcoOrder();
        $team = EcoTeam::factory()->create(['is_active' => true]);
        $this->service->assignTeamToOrder($order, $team);
        $this->service->markInProgress($order);

        $updated = $this->service->markCompleted($order);
        $this->assertEquals('COMPLETED', strtoupper($updated->disposalDetails->eco_status));
        $this->assertEquals('completed', $updated->status);
    }

    /** @test */
    public function it_cancels_if_not_final()
    {
        $order = $this->createEcoOrder();
        $team = EcoTeam::factory()->create(['is_active' => true]);
        $this->service->assignTeamToOrder($order, $team);

        $updated = $this->service->cancelEcoOrder($order, 'test-reason');
        $this->assertEquals('CANCELLED', strtoupper($updated->disposalDetails->eco_status));
        $this->assertEquals('cancelled', $updated->status);
    }
}
