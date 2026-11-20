<?php

namespace App\Enums;

enum WorkOrderStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return __('ui.work_orders.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active, self::InProgress, self::Completed => 'success',
            self::OnHold => 'warning',
            default => 'neutral',
        };
    }
}
