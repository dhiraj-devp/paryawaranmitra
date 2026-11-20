<?php

namespace App\Services\Quotations;

class TaxSettings
{
    public function pending(): bool
    {
        $rates = config('tax.rates');

        return config('tax.enabled') !== true || ! is_array($rates) || $rates === [];
    }
}
