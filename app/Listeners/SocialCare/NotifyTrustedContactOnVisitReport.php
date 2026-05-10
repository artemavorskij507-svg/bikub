<?php

namespace App\Listeners\SocialCare;

use App\Events\SocialCare\VisitReportSubmitted;
use App\Notifications\SocialCare\VisitReportForTrustedContactNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyTrustedContactOnVisitReport implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(VisitReportSubmitted $event): void
    {
        $details = $event->details;
        $report = $event->report;
        $trusted = $details->trustedContact;

        if (! $trusted || ! $trusted->user || ! $trusted->user->email) {
            return;
        }

        if (! $trusted->user->wantsSocialCareNotification('notify_visit_reports')) {
            return;
        }

        $trusted->user->notify(
            new VisitReportForTrustedContactNotification($event->order, $details, $report)
        );
    }
}
