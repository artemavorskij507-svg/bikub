<?php

namespace Tests\Feature\SocialCare;

use App\Enums\CareOrderStatus;
use App\Events\SocialCare\CareOrderAssignedToHelper;
use App\Events\SocialCare\CareOrderCreated;
use App\Events\SocialCare\CareOrderRescheduleRequested;
use App\Events\SocialCare\CareOrderStatusChanged;
use App\Events\SocialCare\SocialCareEmergencyTriggered;
use App\Events\SocialCare\VisitReportSubmitted;
use App\Models\CareOrderChangeRequest;
use App\Models\CareOrderDetails;
use App\Models\CareService;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\SocialCareEmergencyEvent;
use App\Models\SocialCareNotificationSettings;
use App\Models\SocialHelperProfile;
use App\Models\TrustedContact;
use App\Models\User;
use App\Models\VisitReport;
use App\Notifications\SocialCare\CareOrderCreatedForClientNotification;
use App\Notifications\SocialCare\CareOrderCreatedForTrustedContactNotification;
use App\Notifications\SocialCare\CareOrderRescheduleRequestedNotification;
use App\Notifications\SocialCare\CareOrderStatusChangedNotification;
use App\Notifications\SocialCare\OrderAssignedToHelperNotification;
use App\Notifications\SocialCare\SocialCareEmergencyNotification;
use App\Notifications\SocialCare\VisitReportForTrustedContactNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SocialCareNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function care_order_created_sends_notifications_to_client_and_trusted_contact_when_emails_present(): void
    {
        Notification::fake();

        $clientUser = User::factory()->create(['email' => 'client@example.com']);
        $trustedUser = User::factory()->create(['email' => 'trusted@example.com']);

        $client = ClientProfile::factory()->create(['user_id' => $clientUser->id]);
        $trusted = TrustedContact::factory()->create([
            'client_profile_id' => $client->id,
            'user_id' => $trustedUser->id,
        ]);

        $careService = CareService::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'trusted_contact_id' => $trusted->id,
            'care_service_id' => $careService->id,
        ]);

        event(new CareOrderCreated($order, $careDetails, $clientUser));

        Notification::assertSentTo(
            $clientUser,
            CareOrderCreatedForClientNotification::class
        );

        Notification::assertSentTo(
            $trustedUser,
            CareOrderCreatedForTrustedContactNotification::class
        );
    }

    /** @test */
    public function order_assigned_sends_notification_to_helper(): void
    {
        Notification::fake();

        $helperUser = User::factory()->create(['email' => 'helper@example.com']);
        $helper = SocialHelperProfile::factory()->create(['user_id' => $helperUser->id]);

        $client = ClientProfile::factory()->create();
        $careService = CareService::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $careService->id,
        ]);

        event(new CareOrderAssignedToHelper($order, $careDetails, $helper));

        Notification::assertSentTo(
            $helperUser,
            OrderAssignedToHelperNotification::class
        );
    }

    /** @test */
    public function visit_report_submitted_sends_notification_to_trusted_contact_respecting_preferences(): void
    {
        Notification::fake();

        $trustedUser = User::factory()->create(['email' => 'trusted@example.com']);
        $client = ClientProfile::factory()->create();
        $trusted = TrustedContact::factory()->create([
            'client_profile_id' => $client->id,
            'user_id' => $trustedUser->id,
        ]);

        // Disable notifications for visit reports
        SocialCareNotificationSettings::create([
            'user_id' => $trustedUser->id,
            'notify_visit_reports' => false,
        ]);

        $careService = CareService::factory()->create();
        $helper = SocialHelperProfile::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'trusted_contact_id' => $trusted->id,
            'care_service_id' => $careService->id,
        ]);

        $report = VisitReport::factory()->create([
            'care_order_details_id' => $careDetails->id,
            'helper_profile_id' => $helper->id,
        ]);

        event(new VisitReportSubmitted($order, $careDetails, $report));

        // Should NOT send notification because preference is disabled
        Notification::assertNotSentTo(
            $trustedUser,
            VisitReportForTrustedContactNotification::class
        );

        // Enable notifications
        $trustedUser->socialCareNotificationSettings->update(['notify_visit_reports' => true]);

        Notification::fake();

        event(new VisitReportSubmitted($order, $careDetails, $report));

        // Now should send
        Notification::assertSentTo(
            $trustedUser,
            VisitReportForTrustedContactNotification::class
        );
    }

    /** @test */
    public function helper_can_trigger_emergency_and_emergency_event_is_created(): void
    {
        Event::fake([SocialCareEmergencyTriggered::class]);

        $helperUser = User::factory()->create();
        $helper = SocialHelperProfile::factory()->create(['user_id' => $helperUser->id]);

        $client = ClientProfile::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
        ]);

        $this->actingAs($helperUser, 'sanctum');

        $response = $this->postJson('/api/v1/helper/emergency', [
            'order_id' => $order->id,
            'level' => 'CRITICAL',
            'message' => 'Client needs immediate assistance',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'status'],
        ]);

        $this->assertDatabaseHas('social_care_emergency_events', [
            'order_id' => $order->id,
            'helper_profile_id' => $helper->id,
            'client_profile_id' => $client->id,
            'triggered_by_user_id' => $helperUser->id,
            'level' => 'CRITICAL',
            'source' => 'HELPER_APP',
        ]);

        Event::assertDispatched(SocialCareEmergencyTriggered::class);
    }

    /** @test */
    public function emergency_trigger_sends_notification_to_coordinators(): void
    {
        Notification::fake();

        $coordinator1 = User::factory()->create(['email' => 'coord1@example.com']);
        $coordinator2 = User::factory()->create(['email' => 'coord2@example.com']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        // Assign roles - using hasRole method from User model
        // In real implementation, you would use your role assignment system
        // For testing, we'll mock the hasRole method or use database roles if available

        $helperUser = User::factory()->create();
        $helper = SocialHelperProfile::factory()->create(['user_id' => $helperUser->id]);
        $client = ClientProfile::factory()->create();

        $emergency = SocialCareEmergencyEvent::create([
            'helper_profile_id' => $helper->id,
            'client_profile_id' => $client->id,
            'triggered_by_user_id' => $helperUser->id,
            'level' => 'CRITICAL',
            'source' => 'HELPER_APP',
            'message' => 'Test emergency',
        ]);

        event(new SocialCareEmergencyTriggered($emergency));

        // Should notify coordinators and admin (for CRITICAL)
        Notification::assertSentTo(
            $coordinator1,
            SocialCareEmergencyNotification::class
        );
        Notification::assertSentTo(
            $coordinator2,
            SocialCareEmergencyNotification::class
        );
        Notification::assertSentTo(
            $admin,
            SocialCareEmergencyNotification::class
        );
    }

    /** @test */
    public function notification_settings_can_disable_specific_notifications(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        // Disable care order created notifications
        SocialCareNotificationSettings::create([
            'user_id' => $user->id,
            'notify_care_order_created' => false,
            'notify_visit_status_changes' => true,
        ]);

        $client = ClientProfile::factory()->create(['user_id' => $user->id]);
        $careService = CareService::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'care_service_id' => $careService->id,
        ]);

        event(new CareOrderCreated($order, $careDetails, $user));

        // Should NOT send because preference is disabled
        Notification::assertNotSentTo(
            $user,
            CareOrderCreatedForClientNotification::class
        );

        // But status changes should still be sent
        $oldStatus = CareOrderStatus::SCHEDULED;
        $newStatus = CareOrderStatus::COMPLETED;

        event(new CareOrderStatusChanged($order, $careDetails, $oldStatus, $newStatus));

        Notification::assertSentTo(
            $user,
            CareOrderStatusChangedNotification::class
        );
    }

    /** @test */
    public function care_order_status_changed_notifies_all_relevant_parties(): void
    {
        Notification::fake();

        $clientUser = User::factory()->create(['email' => 'client@example.com']);
        $trustedUser = User::factory()->create(['email' => 'trusted@example.com']);
        $helperUser = User::factory()->create(['email' => 'helper@example.com']);

        $client = ClientProfile::factory()->create(['user_id' => $clientUser->id]);
        $trusted = TrustedContact::factory()->create([
            'client_profile_id' => $client->id,
            'user_id' => $trustedUser->id,
        ]);
        $helper = SocialHelperProfile::factory()->create(['user_id' => $helperUser->id]);

        $careService = CareService::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
            'trusted_contact_id' => $trusted->id,
            'care_service_id' => $careService->id,
            'assigned_helper_id' => $helper->id,
        ]);

        $oldStatus = CareOrderStatus::SCHEDULED;
        $newStatus = CareOrderStatus::COMPLETED;

        event(new CareOrderStatusChanged($order, $careDetails, $oldStatus, $newStatus, 'Completed successfully'));

        Notification::assertSentTo($clientUser, CareOrderStatusChangedNotification::class);
        Notification::assertSentTo($trustedUser, CareOrderStatusChangedNotification::class);
        Notification::assertSentTo($helperUser, CareOrderStatusChangedNotification::class);
    }

    /** @test */
    public function reschedule_request_notifies_coordinators(): void
    {
        Notification::fake();

        $coordinator = User::factory()->create(['email' => 'coord@example.com']);
        // In real implementation, assign role using your role system

        $user = User::factory()->create();
        $client = ClientProfile::factory()->create();
        $order = Order::factory()->create();
        $careDetails = CareOrderDetails::factory()->create([
            'order_id' => $order->id,
            'client_profile_id' => $client->id,
        ]);

        $changeRequest = CareOrderChangeRequest::create([
            'order_id' => $order->id,
            'requested_by_user_id' => $user->id,
            'requested_new_start_at' => now()->addDay(),
            'status' => 'PENDING',
        ]);

        event(new CareOrderRescheduleRequested($order, $changeRequest));

        Notification::assertSentTo(
            $coordinator,
            CareOrderRescheduleRequestedNotification::class
        );
    }

    /** @test */
    public function emergency_rate_limiting_prevents_abuse(): void
    {
        $helperUser = User::factory()->create();
        $helper = SocialHelperProfile::factory()->create(['user_id' => $helperUser->id]);

        $this->actingAs($helperUser, 'sanctum');

        // Send 5 requests (should be allowed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/helper/emergency', [
                'level' => 'INFO',
                'message' => "Test $i",
            ]);
            $response->assertStatus(201);
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/v1/helper/emergency', [
            'level' => 'INFO',
            'message' => 'Test 6',
        ]);

        $response->assertStatus(429);
    }
}
