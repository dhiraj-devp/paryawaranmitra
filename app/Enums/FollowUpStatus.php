<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('ui.follow_ups.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Completed => 'success',
            self::Cancelled => 'neutral',
            self::Pending => 'warning',
        };
    }
}
