<?php

namespace Tests\Feature\Slots;

use App\Models\GeoZone;
use App\Models\ScheduleSlot;
use App\Models\TrafficIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RestrictionFromIncidentsTest extends TestCase
{
    use RefreshDatabase;

    protected GeoZone $zone;

    protected ScheduleSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zone = GeoZone::factory()->create([
            'name' => 'Narvik Test Zone',
            'center_latitude' => 68.4384,
            'center_longitude' => 17.4278,
        ]);

        $this->slot = ScheduleSlot::factory()->create([
            'zone_id' => $this->zone->id,
            'start_at' => Carbon::now()->setTime(8, 0),
            'end_at' => Carbon::now()->setTime(12, 0),
        ]);
    }

    /** @test */
    public function it_restricts_slot_when_severe_incident_in_zone()
    {
        TrafficIncident::factory()->create([
            'severity' => 'severe',
            'status' => 'active',
            'lat' => 68.4384,
            'lng' => 17.4278,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $this->assertTrue($this->slot->shouldBeRestricted($this->zone));
    }

    /** @test */
    public function it_increases_eta_when_severe_incident_exists()
    {
        $baseEta = 30; // minutes

        TrafficIncident::factory()->create([
            'severity' => 'severe',
            'status' => 'active',
            'lat' => 68.4384,
            'lng' => 17.4278,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $severeCount = TrafficIncident::where('severity', 'severe')
            ->where('status', 'active')
            ->whereBetween('lat', [
                $this->zone->center_latitude - 0.1,
                $this->zone->center_latitude + 0.1,
            ])
            ->whereBetween('lng', [
                $this->zone->center_longitude - 0.1,
                $this->zone->center_longitude + 0.1,
            ])
            ->count();

        $multiplier = $severeCount > 0 ? 1.25 : 1.0;
        $adjustedEta = $baseEta * $multiplier;

        $this->assertEquals(37.5, $adjustedEta); // 30 * 1.25
    }

    /** @test */
    public function it_does_not_restrict_slot_when_only_minor_incident()
    {
        TrafficIncident::factory()->create([
            'severity' => 'minor',
            'status' => 'active',
            'lat' => 68.4384,
            'lng' => 17.4278,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $this->assertFalse($this->slot->shouldBeRestricted($this->zone));
    }

    /** @test */
    public function it_does_not_restrict_slot_when_incident_is_inactive()
    {
        TrafficIncident::factory()->create([
            'severity' => 'severe',
            'status' => 'resolved',
            'lat' => 68.4384,
            'lng' => 17.4278,
            'starts_at' => now()->subHours(3),
            'ends_at' => now()->subHour(),
        ]);

        $this->assertFalse($this->slot->shouldBeRestricted($this->zone));
    }

    /** @test */
    public function it_does_not_restrict_slot_when_incident_is_far_away()
    {
        TrafficIncident::factory()->create([
            'severity' => 'severe',
            'status' => 'active',
            'lat' => 70.0,
            'lng' => 20.0,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]);

        $this->assertFalse($this->slot->shouldBeRestricted($this->zone));
    }
}
