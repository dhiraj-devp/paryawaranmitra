<?php

namespace App\Enums;

enum EnquirySource: string
{
    case Phone = 'phone';
    case WalkIn = 'walk_in';
    case WhatsApp = 'whatsapp';
    case Website = 'website';
    case Referral = 'referral';
    case Other = 'other';

    public function label(): string
    {
        return __('ui.enquiries.sources.'.$this->value);
    }
}
