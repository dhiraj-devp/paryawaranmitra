<?php

namespace App\Enums;

enum FollowUpMethod: string
{
    case Phone = 'phone';
    case WalkIn = 'walk_in';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Other = 'other';

    public function label(): string
    {
        return __('ui.follow_ups.methods.'.$this->value);
    }
}
