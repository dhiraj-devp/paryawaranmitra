<?php

namespace App\Enums;

enum QuotationLineType: string
{
    case Service = 'service';
    case Custom = 'custom';

    public function label(): string
    {
        return __('ui.quotations.line_types.'.$this->value);
    }
}
