<?php

namespace App\Support;

use InvalidArgumentException;

class Decimal
{
    public static function normalize(string $amount): string
    {
        $amount = trim($amount);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('Invalid decimal amount.');
        }

        return bcadd($amount, '0', 2);
    }

    public static function add(string $left, string $right): string
    {
        return bcadd(self::normalize($left), self::normalize($right), 2);
    }

    public static function sub(string $left, string $right): string
    {
        return bcsub(self::normalize($left), self::normalize($right), 2);
    }

    public static function mul(string $left, string $right): string
    {
        $product = bcmul(self::normalize($left), self::normalize($right), 6);

        return self::roundHalfUp($product, 2);
    }

    public static function comp(string $left, string $right): int
    {
        return bccomp(self::normalize($left), self::normalize($right), 2);
    }

    public static function roundHalfUp(string $amount, int $scale): string
    {
        $negative = str_starts_with($amount, '-');
        $absolute = ltrim($amount, '-');

        if (! preg_match('/^\d+(\.\d+)?$/', $absolute)) {
            throw new InvalidArgumentException('Invalid decimal amount.');
        }

        $increment = $scale > 0 ? '0.'.str_repeat('0', $scale).'5' : '0.5';
        $rounded = bcadd($absolute, $increment, $scale);

        return $negative ? '-'.$rounded : $rounded;
    }
}
