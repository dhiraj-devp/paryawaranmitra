<?php

namespace App\Support;

class Money
{
    public static function normalize(string $amount): string
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            return $amount;
        }

        return $matches[1].'.'.str_pad($matches[2] ?? '', 2, '0');
    }

    public static function inr(null|string|int $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $amount = (string) $amount;

        if (! preg_match('/^(-)?(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            return $amount;
        }

        $whole = $matches[2];
        $fraction = str_pad($matches[3] ?? '00', 2, '0');

        if (strlen($whole) > 3) {
            $tail = substr($whole, -3);
            $head = substr($whole, 0, -3);
            $head = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $head) ?? $head;
            $whole = $head.','.$tail;
        }

        return ($matches[1] ?? '').'₹'.$whole.'.'.$fraction;
    }
}
