<?php

namespace App\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case FollowUp = 'follow_up';
    case Converted = 'converted';
    case OnHold = 'on_hold';
    case Lost = 'lost';

    public function label(): string
    {
        return __('ui.enquiries.statuses.'.$this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Converted => 'success',
            self::Lost => 'danger',
            self::New => 'neutral',
            default => 'warning',
        };
    }
}
