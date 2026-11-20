<?php

namespace App\Enums;

enum ClientConstitution: string
{
    case Proprietorship = 'proprietorship';
    case Partnership = 'partnership';
    case Llp = 'llp';
    case PrivateLimited = 'private_limited';
    case PublicLimited = 'public_limited';
    case Trust = 'trust';
    case Society = 'society';
    case Government = 'government';
    case Other = 'other';

    public function label(): string
    {
        return __('ui.clients.constitutions.'.$this->value);
    }
}
