<?php

namespace Tests\Feature\Public\Repair;

use App\Enums\ServiceType;
use App\Models\Order;
use App\Models\RepairProject;
use App\Models\RepairStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairIntakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_complex_repair_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('repair.request.store'), [
            'object_type' => 'apartment',
            'repair_type' => 'capital',
            'area_sqm' => 75.5,
            'description' => 'Нужен капитальный ремонт квартиры: замена коммуникаций, выравнивание стен, укладка плитки.',
            'address_line' => '123 Main Street',
            'postal_code' => '12345',
            'city' => 'Oslo',
            'desired_start_at' => now()->addMonth()->format('Y-m-d'),
            'desired_finish_at' => now()->addMonths(3)->format('Y-m-d'),
            'budget_expectation' => 'до 500 000 NOK',
            'notes' => 'Есть дизайн-проект',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        // Проверяем, что создан Order с правильным типом
        $order = Order::where('user_id', $user->id)
            ->where('service_type', ServiceType::COMPLEX_REPAIR->value)
            ->first();

        $this->assertNotNull($order);
        $this->assertEquals('pending_review', $order->status);

        // Проверяем, что создан RepairProject
        $project = RepairProject::where('order_id', $order->id)->first();
        $this->assertNotNull($project);
        $this->assertEquals('assessment', $project->status);
        $this->assertEquals('123 Main Street', $project->address_line);
        $this->assertEquals('Oslo', $project->city);

        // Проверяем, что созданы этапы (>= 3)
        $stages = RepairStage::where('repair_project_id', $project->id)->get();
        $this->assertGreaterThanOrEqual(3, $stages->count());
        $stageNames = $stages->pluck('name')->toArray();
        $this->assertContains('Оценка и планирование', $stageNames);
        $this->assertContains('Демонтаж и подготовка', $stageNames);
    }

    public function test_repair_request_requires_basic_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('repair.request.store'), [
            // Отсутствуют обязательные поля
        ]);

        $response->assertSessionHasErrors(['object_type', 'repair_type', 'description', 'address_line', 'postal_code', 'city']);
    }

    public function test_repair_request_validates_desired_finish_after_start(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('repair.request.store'), [
            'object_type' => 'apartment',
            'repair_type' => 'capital',
            'description' => 'Test description',
            'address_line' => '123 Main Street',
            'postal_code' => '12345',
            'city' => 'Oslo',
            'desired_start_at' => now()->addMonths(3)->format('Y-m-d'),
            'desired_finish_at' => now()->addMonth()->format('Y-m-d'), // Ранее начала
        ]);

        $response->assertSessionHasErrors(['desired_finish_at']);
    }

    public function test_guest_cannot_access_request_form(): void
    {
        $response = $this->get(route('repair.request'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_submit_request(): void
    {
        $response = $this->post(route('repair.request.store'), [
            'object_type' => 'apartment',
            'repair_type' => 'capital',
            'description' => 'Test',
            'address_line' => '123 Main',
            'postal_code' => '12345',
            'city' => 'Oslo',
        ]);

        $response->assertRedirect(route('login'));
    }
}
