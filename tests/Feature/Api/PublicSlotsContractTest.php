<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class PublicSlotsContractTest extends TestCase
{
    public function test_public_slots_requires_date_and_returns_json_422(): void
    {
        $response = $this->getJson('/api/v1/public/slots');

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['date']);
    }
}
