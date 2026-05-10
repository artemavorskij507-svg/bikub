<?php

namespace App\Events;

use App\Models\RepairProject;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairProjectStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RepairProject $project,
        public string $oldStatus,
        public string $newStatus
    ) {
    }
}
<?php

namespace App\Events;

use App\Models\RepairProject;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairProjectStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public RepairProject $project,
        public string $oldStatus,
        public string $newStatus
    ) {
    }
}

