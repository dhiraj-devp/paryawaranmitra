<?php

namespace App\Enums;

enum Portal: string
{
    case Admin = 'admin';
    case Staff = 'staff';

    public function label(): string
    {
        return __('ui.portals.'.$this->value);
    }
}
