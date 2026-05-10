<?php

namespace App\Providers;

use App\Events\AdExpiredEvent;
use App\Events\ClaimCreated;
use App\Events\ClaimMessageCreated;
use App\Events\ClaimOpened;
use App\Events\ClaimRejected;
use App\Events\ClaimResolved;
use App\Events\ClaimSlaBreached;
use App\Events\HandymanAssignmentStatusChanged;
use App\Events\HandymanJobCompleted;
use App\Events\HandymanOrderRequested;
use App\Events\OrderCanceled;
use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderPaid;
use App\Events\OrderPlaced;
use App\Events\RepairProjectCreated;
use App\Events\RepairProjectStatusUpdated;
use App\Events\RepairUpdateCreated;
use App\Events\ClientConfirmationRequested;
use App\Events\OrderAssigned;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Events\PayoutCreated;
use App\Events\PayoutPaid;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskFailed;
use App\Events\TrafficIncidentUpdated;
use App\Events\UserRegistered;
use App\Events\ServiceJobCreated;
use App\Events\Operations\JobStatusChanged;
use App\Domain\Exceptions\Events\OperationExceptionOpened as DomainOperationExceptionOpened;
use App\Domain\Operations\Events\ServiceJobStatusChanged as DomainServiceJobStatusChanged;
use App\Domain\Sla\Events\SlaBreached as DomainSlaBreached;
use App\Domain\Sla\Events\SlaWarningRaised as DomainSlaWarningRaised;
use App\Listeners\ApplyLoyaltyAndPromocodes;
use App\Listeners\BroadcastExceptionOpenedBridge;
use App\Listeners\BroadcastJobStatusBridge;
use App\Listeners\BroadcastOperationExceptionOpened;
use App\Listeners\BroadcastServiceJobStatusChanged;
use App\Listeners\BroadcastSlaBreachedBridge;
use App\Listeners\BroadcastSlaWarningBridge;
use App\Listeners\CreateSlaTimersWhenServiceJobCreated;
use App\Listeners\EmitOrderWebhookForN8n;
use App\Listeners\EmitRepairWebhookForN8n;
use App\Listeners\GenerateTasksForOrderPaid;
use App\Listeners\LogOrderActivity;
use App\Listeners\LogUserRegistered;
use App\Listeners\Notifications\PushOrderEventToFeed;
use App\Listeners\Notifications\PushSocialCareEventToFeed;
use App\Listeners\Notifications\LogBusinessEvent;
use App\Listeners\NormalizeOrderToServiceJobListener;
use App\Listeners\NotifyCustomerAboutClaimStatus;
use App\Listeners\NotifyCustomerAboutHandymanAssignment;
use App\Listeners\NotifyCustomerAboutHandymanOrder;
use App\Listeners\NotifyCustomerAboutRepairProject;
use App\Listeners\NotifyCustomerAboutRepairUpdate;
use App\Listeners\NotifyExecutorAboutAssignmentStatus;
use App\Listeners\NotifyOperatorAboutClaim;
use App\Listeners\NotifyProjectManagerAboutRepairProject;
use App\Listeners\ProcessOrderPayment;
use App\Listeners\RequestDispatchForServiceJob;
use App\Listeners\SyncOrderStatusFromServiceJob;
use App\Listeners\WriteTimelineWhenServiceJobCreated;
use App\Listeners\WriteTimelineWhenServiceJobStatusChanged;
use App\Listeners\OpenExceptionWhenSlaBreached;
use App\Listeners\OpenExceptionWhenSlaWarningRaised;
use App\Listeners\OrderStatusSyncFromServiceJobListener;
use App\Listeners\RecalculateHandymanKpiForAssignment;
use App\Listeners\RecalculateHandymanKpiForClaim;
use App\Listeners\RestrictSlotsForIncident;
use App\Listeners\ScheduleEcoCertificateGeneration;
use App\Listeners\SendClaimWebhookToN8n;
use App\Listeners\SendTaskWebhook;
use App\Listeners\SendWebhookToN8n;
use App\Listeners\UpdateSlotUtilization;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        UserRegistered::class => [
            LogUserRegistered::class,
        ],
        OrderCreated::class => [
            PushOrderEventToFeed::class,
            LogBusinessEvent::class,
        ],
        OrderPaid::class => [
            GenerateTasksForOrderPaid::class,
            PushOrderEventToFeed::class,
        ],
        OrderCompleted::class => [
            ScheduleEcoCertificateGeneration::class,
            PushOrderEventToFeed::class,
            LogBusinessEvent::class,
        ],
        OrderAssigned::class => [
            LogBusinessEvent::class,
        ],
        OrderStatusChanged::class => [
            LogBusinessEvent::class,
        ],
        PaymentStatusChanged::class => [
            LogBusinessEvent::class,
        ],
        ClientConfirmationRequested::class => [
            LogBusinessEvent::class,
        ],
        PayoutCreated::class => [
            LogBusinessEvent::class,
        ],
        PayoutPaid::class => [
            LogBusinessEvent::class,
        ],
        OrderCanceled::class => [
            PushOrderEventToFeed::class,
        ],
        OrderPlaced::class => [
            ProcessOrderPayment::class,           // ⚡ Одразу (платіж)
            ApplyLoyaltyAndPromocodes::class,     // ⚡ Одразу (знижка/бали)
            LogOrderActivity::class,              // ⏱️ Логування
            NormalizeOrderToServiceJobListener::class,
        ],
        TaskCreated::class => [
            UpdateSlotUtilization::class.'@handleTaskCreated',
        ],
        TaskAssigned::class => [
            SendTaskWebhook::class.'@handleTaskAssigned',
        ],
        TaskCompleted::class => [
            SendTaskWebhook::class.'@handleTaskCompleted',
            UpdateSlotUtilization::class.'@handleTaskCompleted',
        ],
        TaskFailed::class => [
            SendTaskWebhook::class.'@handleTaskFailed',
        ],
        TrafficIncidentUpdated::class => [
            RestrictSlotsForIncident::class,
        ],
        AdExpiredEvent::class => [
            SendWebhookToN8n::class,
        ],
        HandymanOrderRequested::class => [
            NotifyCustomerAboutHandymanOrder::class,
            EmitOrderWebhookForN8n::class,
        ],
        HandymanAssignmentStatusChanged::class => [
            NotifyCustomerAboutHandymanAssignment::class,
            NotifyExecutorAboutAssignmentStatus::class,
        ],
        HandymanJobCompleted::class => [
            RecalculateHandymanKpiForAssignment::class,
        ],
        RepairProjectCreated::class => [
            NotifyCustomerAboutRepairProject::class,
            NotifyProjectManagerAboutRepairProject::class,
            EmitRepairWebhookForN8n::class,
        ],
        RepairProjectStatusUpdated::class => [
            // Placeholder для будущих слушателей
        ],
        RepairUpdateCreated::class => [
            NotifyCustomerAboutRepairUpdate::class,
        ],
        ClaimCreated::class => [
            SendClaimWebhookToN8n::class.'@handleClaimCreated',
        ],
        ClaimMessageCreated::class => [
            SendClaimWebhookToN8n::class.'@handleClaimMessageCreated',
        ],
        ClaimSlaBreached::class => [
            SendClaimWebhookToN8n::class.'@handleClaimSlaBreached',
        ],
        ClaimOpened::class => [
            NotifyOperatorAboutClaim::class,
        ],
        ClaimCreated::class => [
            SendClaimWebhookToN8n::class.'@handleClaimCreated',
        ],
        ClaimMessageCreated::class => [
            SendClaimWebhookToN8n::class.'@handleClaimMessageCreated',
        ],
        ClaimSlaBreached::class => [
            SendClaimWebhookToN8n::class.'@handleClaimSlaBreached',
        ],
        ClaimResolved::class => [
            NotifyCustomerAboutClaimStatus::class,
            RecalculateHandymanKpiForClaim::class,
        ],
        ClaimRejected::class => [
            NotifyCustomerAboutClaimStatus::class,
            RecalculateHandymanKpiForClaim::class,
        ],
        // Social Care Events
        \App\Events\SocialCare\CareOrderCreated::class => [
            \App\Listeners\SocialCare\SendNotificationsOnCareOrderCreated::class,
        ],
        \App\Events\SocialCare\CarePlanCreated::class => [
            \App\Listeners\SocialCare\SendNotificationsOnCarePlanCreated::class,
        ],
        \App\Events\SocialCare\CareOrderAssignedToHelper::class => [
            \App\Listeners\SocialCare\NotifyHelperOnOrderAssigned::class,
        ],
        \App\Events\SocialCare\CareOrderStatusChanged::class => [
            \App\Listeners\SocialCare\SendNotificationsOnCareOrderStatusChanged::class,
            PushSocialCareEventToFeed::class,
        ],
        \App\Events\SocialCare\VisitReportSubmitted::class => [
            \App\Listeners\SocialCare\NotifyTrustedContactOnVisitReport::class,
            PushSocialCareEventToFeed::class,
        ],
        \App\Events\SocialCare\CareOrderRescheduleRequested::class => [
            \App\Listeners\SocialCare\NotifyCoordinatorsOnRescheduleRequest::class,
        ],
        \App\Events\SocialCare\SocialCareEmergencyTriggered::class => [
            \App\Listeners\SocialCare\NotifyCoordinatorsOnSocialCareEmergency::class,
        ],
        ServiceJobCreated::class => [
            WriteTimelineWhenServiceJobCreated::class,
            CreateSlaTimersWhenServiceJobCreated::class,
            RequestDispatchForServiceJob::class,
        ],
        DomainServiceJobStatusChanged::class => [
            WriteTimelineWhenServiceJobStatusChanged::class,
            SyncOrderStatusFromServiceJob::class,
            BroadcastJobStatusBridge::class,
            BroadcastServiceJobStatusChanged::class,
        ],
        DomainSlaWarningRaised::class => [
            OpenExceptionWhenSlaWarningRaised::class,
            BroadcastSlaWarningBridge::class,
        ],
        DomainSlaBreached::class => [
            OpenExceptionWhenSlaBreached::class,
            BroadcastSlaBreachedBridge::class,
        ],
        DomainOperationExceptionOpened::class => [
            BroadcastExceptionOpenedBridge::class,
            BroadcastOperationExceptionOpened::class,
        ],
        JobStatusChanged::class => [
            OrderStatusSyncFromServiceJobListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
