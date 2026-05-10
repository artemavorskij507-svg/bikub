<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentEcoResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): User
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        return $user;
    }

    /** @test */
    public function disposal_item_resource_pages_are_accessible()
    {
        $this->authenticate();
        $this->get(route('filament.resources.disposal-items.index'))->assertStatus(200);
        $this->get(route('filament.resources.disposal-items.create'))->assertStatus(200);
    }

    /** @test */
    public function disposal_partner_resource_pages_are_accessible()
    {
        $this->authenticate();
        $this->get(route('filament.resources.disposal-partners.index'))->assertStatus(200);
        $this->get(route('filament.resources.disposal-partners.create'))->assertStatus(200);
    }

    /** @test */
    public function eco_team_resource_pages_are_accessible()
    {
        $this->authenticate();
        $this->get(route('filament.resources.eco-teams.index'))->assertStatus(200);
        $this->get(route('filament.resources.eco-teams.create'))->assertStatus(200);
    }

    /** @test */
    public function orders_index_is_accessible_and_eco_filter_does_not_error()
    {
        $this->authenticate();
        $this->get(route('filament.resources.orders.index'))->assertStatus(200);

        // Apply ECO filter; Filament passes filter state via querystring.
        $this->get(route('filament.resources.orders.index', [
            'tableFilters' => [
                'eco' => ['value' => 'only'],
            ],
        ]))->assertStatus(200);
    }
}
