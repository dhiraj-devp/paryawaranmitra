<?php

namespace App\Enums;

enum RecordStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __('ui.status.'.$this->value);
    }
}
