<?php

namespace App\Enums;

enum ChargeSide: string
{
    case Government = 'government';
    case Consultancy = 'consultancy';

    public function label(): string
    {
        return __('ui.quotations.charge_sides.'.$this->value);
    }
}
