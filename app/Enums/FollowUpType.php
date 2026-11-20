<?php

namespace App\Enums;

enum FollowUpType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case WhatsApp = 'whatsapp';
    case Other = 'other';

    public function label(): string
    {
        return __('ui.follow_ups.types.'.$this->value);
    }

    public static function fromMethod(FollowUpMethod $method): self
    {
        return match ($method) {
            FollowUpMethod::Phone => self::Call,
            FollowUpMethod::WalkIn => self::Meeting,
            FollowUpMethod::WhatsApp => self::WhatsApp,
            FollowUpMethod::Email => self::Email,
            FollowUpMethod::Other => self::Other,
        };
    }
}
