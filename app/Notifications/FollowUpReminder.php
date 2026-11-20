<?php

namespace App\Notifications;

use App\Models\FollowUp;
use Illuminate\Notifications\Notification;

class FollowUpReminder extends Notification
{
    public function __construct(private FollowUp $followUp) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $locale = $notifiable->locale ?? config('app.locale');
        $when = $this->followUp->scheduled_at?->timezone(config('app.timezone'))->format('d-m-Y H:i');

        return [
            'title' => __('ui.follow_ups.reminder_title', [], $locale),
            'body' => __('ui.follow_ups.reminder_body', [
                'when' => $when,
                'notes' => $this->followUp->notes,
            ], $locale),
            'follow_up_id' => $this->followUp->id,
        ];
    }
}
