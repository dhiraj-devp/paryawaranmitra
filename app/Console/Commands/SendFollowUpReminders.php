<?php

namespace App\Console\Commands;

use App\Actions\FollowUps\SendDueFollowUpReminders;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'follow-ups:remind';

    protected $description = 'Notify assigned staff once when a follow-up reminder is due';

    public function handle(SendDueFollowUpReminders $action): int
    {
        $sent = $action->execute();
        $this->info("Sent {$sent} follow-up reminders.");

        return self::SUCCESS;
    }
}
